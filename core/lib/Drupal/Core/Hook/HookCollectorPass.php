<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

use Drupal\Component\Annotation\Doctrine\StaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Extension\ProceduralCall;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\HookAfter;
use Drupal\Core\Hook\Attribute\HookBefore;
use Drupal\Core\Hook\Attribute\HookFirst;
use Drupal\Core\Hook\Attribute\HookLast;
use Drupal\Core\Hook\Attribute\HookOrderGroup;
use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\Core\Hook\Attribute\StopProceduralHookScan;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects and registers hook implementations.
 *
 * A hook implementation is a class in a Drupal\modulename\Hook namespace
 * where either the class itself or the methods have a #[Hook] attribute.
 * These classes are automatically registered as autowired services.
 *
 * Services for procedural implementation of hooks are also registered
 * using the ProceduralCall class.
 *
 * Finally, a hook_implementations_map container parameter is added. This
 * contains a mapping from [hook,class,method] to the module name.
 */
class HookCollectorPass implements CompilerPassInterface {

  /**
   * An associative array of hook implementations.
   *
   * Keys are hook, module, class. Values are a list of methods.
   */
  protected array $implementations = [];

  /**
   * An associative array of hook implementations.
   *
   * Keys are hook, module and an empty string value.
   *
   * @see hook_module_implements_alter()
   */
  protected array $moduleImplements = [];

  /**
   * A list of include files.
   *
   * (This is required only for BC.)
   */
  protected array $includes = [];

  /**
   * A list of functions implementing hook_module_implements_alter().
   *
   * (This is required only for BC.)
   */
  protected array $moduleImplementsAlters = [];

  /**
   * A list of functions implementing hook_hook_info().
   *
   * (This is required only for BC.)
   */
  private array $hookInfo = [];

  /**
   * A list of .inc files.
   */
  private array $groupIncludes = [];

  /**
   * A list of implementations to reprioritize.
   */
  protected array $moduleAttributes = [];

  /**
   * An organized list of hooks to reorder.
   */
  protected array $orderMap = [];

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    $collector = static::collectAllHookImplementations($container->getParameter('container.modules'), $container);
    $map = [];
    $container->register(ProceduralCall::class, ProceduralCall::class)
      ->addArgument($collector->includes);
    $groupIncludes = [];
    foreach ($collector->hookInfo as $function) {
      foreach ($function() as $hook => $info) {
        if (isset($collector->groupIncludes[$info['group']])) {
          $groupIncludes[$hook] = $collector->groupIncludes[$info['group']];
        }
      }
    }
    $definition = $container->getDefinition('module_handler');
    $definition->setArgument('$groupIncludes', $groupIncludes);
    foreach ($this->moduleAttributes as $module => $classAttributes) {
      foreach ($classAttributes as $class => $methodAttributes) {
        foreach ($methodAttributes as $method => $attributes) {
          foreach ($attributes as $attribute) {
            $attribute = $attribute->newInstance();
            switch (get_class($attribute)) {
              case Hook::class:
                self::checkForProceduralOnlyHooks($attribute->hook, $class);
                $this->addFromAttribute($attribute, $class, $module);
                break;

              case HookAfter::class:
                $this->orderMap[$hook][$class][$method]['after'] = $attribute->modules;
                break;

              case HookBefore::class:
                $this->orderMap[$hook][$class][$method]['before'] = $attribute->modules;
                break;

              case HookFirst::class:
                $this->orderMap[$hook][$class][$method]['first'] = 9999;
                break;

              case HookLast::class:
                $this->orderMap[$hook][$class][$method]['last'] = -9999;
                break;

              case HookOrderGroup::class:
                $this->orderMap[$hook][$class][$method]['sort'] = $attribute->group;
                break;
            }
          }
        }
      }
    }

    foreach ($collector->moduleImplements as $hook => $moduleImplements) {
      foreach ($collector->moduleImplementsAlters as $alter) {
        $alter($moduleImplements, $hook);
      }
      $priority = 0;
      foreach ($moduleImplements as $module => $v) {
        foreach ($collector->implementations[$hook][$module] as $class => $method_hooks) {
          if ($container->has($class)) {
            $definition = $container->findDefinition($class);
          }
          else {
            $definition = $container
              ->register($class, $class)
              ->setAutowired(TRUE);
          }
          foreach ($method_hooks as $method) {
            $map[$hook][$class][$method] = $module;
            $definition->addTag('kernel.event_listener', [
              'event' => "drupal_hook.$hook",
              'method' => $method,
              'priority' => $priority,
            ]);
          }
        }
      }
    }
    $container->setParameter('hook_implementations_map', $map);

    foreach ($this->orderMap as $hook => $classes) {
      foreach ($classes as $class => $methods) {
        foreach ($methods as $method => $actions) {
          foreach ($actions as $action => $others) {
            switch ($action) {
              case 'first':
                $this->changePriority($container, $hook, "$class::$method", TRUE);
                break;

              case 'before':
                // @todo $others likely needs to be updated.
                $this->changePriority($container, $hook, "$class::$method", TRUE, $others);
                break;

              case 'after':
                // @todo $others likely needs to be updated.
                $this->changePriority($container, $hook, "$class::$method", FALSE, $others);
                break;

              case 'last':
                $this->changePriority($container, $hook, "$class::$method", FALSE);
                break;
            }
          }
        }
      }
    }
  }

  /**
   * Collects all hook implementations.
   *
   * @param array $module_filenames
   *   An associative array. Keys are the module names, values are relevant
   *   info yml file path.
   * @param Symfony\Component\DependencyInjection\ContainerBuilder|null $container
   *   The container.
   *
   * @return static
   *   A HookCollectorPass instance holding all hook implementations and
   *   include file information.
   *
   * @internal
   *   This method is only used by ModuleHandler.
   *
   * * @todo Pass only $container when ModuleHandler->add is removed https://www.drupal.org/project/drupal/issues/3481778
   */
  public static function collectAllHookImplementations(array $module_filenames, ?ContainerBuilder $container = NULL): static {
    $modules = array_map(fn ($x) => preg_quote($x, '/'), array_keys($module_filenames));
    // Longer modules first.
    usort($modules, fn($a, $b) => strlen($b) - strlen($a));
    $module_preg = '/^(?<function>(?<module>' . implode('|', $modules) . ')_(?!preprocess_)(?!update_\d)(?<hook>[a-zA-Z0-9_\x80-\xff]+$))/';
    $collector = new static();
    foreach ($module_filenames as $module => $info) {
      $skip_procedural = FALSE;
      if ($container?->hasParameter("$module.hooks_converted")) {
        $skip_procedural = $container->getParameter("$module.hooks_converted");
      }
      $collector->collectModuleHookImplementations(dirname($info['pathname']), $module, $module_preg, $skip_procedural);
    }
    return $collector;
  }

  /**
   * Collects procedural and Attribute hook implementations.
   *
   * @param $dir
   *   The directory in which the module resides.
   * @param $module
   *   The name of the module.
   * @param $module_preg
   *   A regular expression matching every module, longer module names are
   *   matched first.
   * @param $skip_procedural
   *   Skip the procedural check for the current module.
   */
  protected function collectModuleHookImplementations($dir, $module, $module_preg, bool $skip_procedural): void {
    $hook_file_cache = FileCacheFactory::get('hook_implementations');
    $procedural_hook_file_cache = FileCacheFactory::get('procedural_hook_implementations:' . $module_preg);
    $this->moduleAttributes[$module] = [];

    $iterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::FOLLOW_SYMLINKS);
    $iterator = new \RecursiveCallbackFilterIterator($iterator, static::filterIterator(...));
    $iterator = new \RecursiveIteratorIterator($iterator);
    /** @var \RecursiveDirectoryIterator | \RecursiveIteratorIterator $iterator*/
    foreach ($iterator as $fileinfo) {
      assert($fileinfo instanceof \SplFileInfo);
      $extension = $fileinfo->getExtension();
      $filename = $fileinfo->getPathname();

      if (($extension === 'module' || $extension === 'profile') && !$iterator->getDepth() && !$skip_procedural) {
        // There is an expectation for all modules and profiles to be loaded.
        // .module and .profile files are not supposed to be in subdirectories.
        // These need to be loaded even if the module has no procedural hooks.
        include_once $filename;
      }
      if ($extension === 'php') {
        $cached = $hook_file_cache->get($filename);
        // @todo remove this comment.
        // $cached = FALSE;
        if ($cached) {
          $class = $cached['class'];
          $attributes = $cached['attributes'];
        }
        else {
          $namespace = preg_replace('#^src/#', "Drupal/$module/", $iterator->getSubPath());
          $class = $namespace . '/' . $fileinfo->getBasename('.php');
          $class = str_replace('/', '\\', $class);
          if (class_exists($class)) {
            $reflectionClass = new \ReflectionClass($class);
            $reflectionClass = new \ReflectionClass($class);
            $attributes['__invoke'] = $reflectionClass->getAttributes();
            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $methodName => $methodReflection) {
              $attributes[$methodName] = $methodReflection->getAttributes();
            }
            $hook_file_cache->set($filename, ['class' => $class, 'attributes' => $attributes]);
          }
          else {
            $attributes = [];
          }
        }
        $this->moduleAttributes[$module][$class] = array_merge($this->moduleAttributes[$module][$class] ?? [], $attributes);
      }
      elseif (!$skip_procedural) {
        $implementations = $procedural_hook_file_cache->get($filename);
        if ($implementations === NULL) {
          $finder = MockFileFinder::create($filename);
          $parser = new StaticReflectionParser('', $finder);
          $implementations = [];
          foreach ($parser->getMethodAttributes() as $function => $attributes) {
            if (StaticReflectionParser::hasAttribute($attributes, StopProceduralHookScan::class)) {
              break;
            }
            if (!StaticReflectionParser::hasAttribute($attributes, LegacyHook::class) && preg_match($module_preg, $function, $matches)) {
              $implementations[] = ['function' => $function, 'module' => $matches['module'], 'hook' => $matches['hook']];
            }
          }
          $procedural_hook_file_cache->set($filename, $implementations);
        }
        foreach ($implementations as $implementation) {
          $this->addProceduralImplementation($fileinfo, $implementation['hook'], $implementation['module'], $implementation['function']);
        }
      }
      if ($extension === 'inc') {
        $parts = explode('.', $fileinfo->getFilename());
        if (count($parts) === 3 && $parts[0] === $module) {
          $this->groupIncludes[$parts[1]][] = $filename;
        }
      }
    }
  }

  /**
   * Filter iterator callback. Allows include files and .php files in src/Hook.
   */
  protected static function filterIterator(\SplFileInfo $fileInfo, $key, \RecursiveDirectoryIterator $iterator): bool {
    $sub_path_name = $iterator->getSubPathname();
    $extension = $fileInfo->getExtension();
    if (str_starts_with($sub_path_name, 'src/Hook/')) {
      return $iterator->isDir() || $extension === 'php';
    }
    if ($iterator->isDir()) {
      if ($sub_path_name === 'src' || $sub_path_name === 'src/Hook') {
        return TRUE;
      }
      // glob() doesn't support streams but scandir() does.
      return !in_array($fileInfo->getFilename(), ['tests', 'js', 'css']) && !array_filter(scandir($key), fn ($filename) => str_ends_with($filename, '.info.yml'));
    }
    return in_array($extension, ['inc', 'module', 'profile', 'install']);
  }

  /**
   * Adds a Hook attribute implementation.
   *
   * @param \Drupal\Core\Hook\Attribute\Hook $hook
   *   A hook attribute.
   * @param $class
   *   The class in which said attribute resides in.
   * @param $module
   *   The module in which the class resides in.
   */
  protected function addFromAttribute(Hook $hook, $class, $module): void {
    if ($hook->module) {
      $module = $hook->module;
    }
    $this->moduleImplements[$hook->hook][$module] = '';
    $this->implementations[$hook->hook][$module][$class][] = $hook->method;
  }

  /**
   * Adds a procedural hook implementation.
   *
   * @param \SplFileInfo $fileinfo
   *   The file this procedural implementation is in. (You don't say)
   * @param string $hook
   *   The name of the hook. (Huh, right?)
   * @param string $module
   *   The name of the module. (Truly shocking!)
   * @param string $function
   *   The name of function implementing the hook. (Wow!)
   */
  protected function addProceduralImplementation(\SplFileInfo $fileinfo, string $hook, string $module, string $function): void {
    $this->addFromAttribute(new Hook($hook, $module . '_' . $hook), ProceduralCall::class, $module);
    if ($hook === 'hook_info') {
      $this->hookInfo[] = $function;
    }
    if ($hook === 'module_implements_alter') {
      // @todo confirm this is skipped when #[LegacyHook] should be.
      $this->moduleImplementsAlters[] = $function;
    }
    if ($fileinfo->getExtension() !== 'module') {
      $this->includes[$function] = $fileinfo->getPathname();
    }
  }

  /**
   * This method is only to be used by ModuleHandler.
   *
   * @internal
   */
  public function loadAllIncludes(): void {
    foreach ($this->includes as $include) {
      include_once $include;
    }
  }

  /**
   * This method is only to be used by ModuleHandler.
   *
   * @internal
   */
  public function getImplementations(): array {
    return $this->implementations;
  }

  /**
   * Checks for hooks which can't be supported in classes.
   *
   * @param \Drupal\Core\Hook\Attribute\Hook $hook
   *   The hook to check.
   * @param string $class
   *   The class the hook is implemented on.
   */
  public static function checkForProceduralOnlyHooks(Hook $hook, string $class): void {
    $staticDenyHooks = [
      'hook_info',
      'install',
      'module_implements_alter',
      'requirements',
      'schema',
      'uninstall',
      'update_last_removed',
      'install_tasks',
      'install_tasks_alter',
    ];

    if (in_array($hook->hook, $staticDenyHooks) || preg_match('/^(post_update_|preprocess_|update_\d+$)/', $hook->hook)) {
      throw new \LogicException("The hook $hook->hook on class $class does not support attributes and must remain procedural.");
    }
  }

  /**
   * Change the priority of a hook implementation.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The container builder.
   * @param string $hook
   *   The name of the hook.
   * @param string $class_and_method
   *   Class and method separated by :: containing the hook implementation which
   *   should be changed.
   * @param bool $should_be_larger
   *   TRUE for before/first, FALSE for after/last. Larger priority listeners
   *   fire first.
   * @param array|null $others
   *   Other hook implementations to compare to, if any. The array is keyed by
   *   string containing a class and method separated by ::, the value is not
   *   used.
   *
   * @return void
   */
  protected function changePriority(ContainerBuilder $container, string $hook, string $class_and_method, bool $should_be_larger, ?array $others = NULL): void {
    $events = $this->orderGroup[$hook] ?? ["drupal_hook.$hook"];
    foreach ($container->findTaggedServiceIds('kernel.event_listener') as $id => $attributes) {
      foreach ($attributes as $key => $tag) {
        if (in_array($tag['event'], $events)) {
          $index = "$id.$key";
          $priority = $tag['priority'];
          // Symfony documents event listener priorities to be integers,
          // HookCollectorPass sets them to be integers, ::setPriority() only
          // accepts integers.
          assert(is_int($priority));
          $priorities[$index] = $priority;
          $specifier = "$id::" . $tag['method'];
          if ($class_and_method === $specifier) {
            $index_this = $index;
          }
          // If $others is specified by ::before() / ::after() then for
          // comparison only the priority of those matter.
          // For ::first() / ::last() the priority of every other hook
          // matters.
          elseif (!isset($others) || isset($others[$specifier])) {
            $priorities_other[] = $priority;
          }
        }
      }
    }
    if (!isset($index_this) || !isset($priorities) || !isset($priorities_other)) {
      return;
    }
    // The priority of the hook being changed.
    $priority_this = $priorities[$index_this];
    // The priority of the hook being compared to.
    $priority_other = $should_be_larger ? max($priorities_other) : min($priorities_other);
    // If the order is correct there is nothing to do. If the two priorities
    // are the same then the order is undefined and so it can't be correct.
    // If they are not the same and $priority_this is already larger exactly
    // when $should_be_larger says then it's the correct order.
    if ($priority_this !== $priority_other && ($should_be_larger === ($priority_this > $priority_other))) {
      return;
    }
    $priority_new = $priority_other + ($should_be_larger ? 1 : -1);
    // For ::first() / ::last() this new priority is already larger/smaller
    // than all existing priorities but for ::before() / ::after() it might
    // belong to an already existing hook. In this case set the new priority
    // temporarily to be halfway between $priority_other and $priority_new
    // then give all hook implementations new, integer priorities keeping this
    // new order. This ensures the hook implementation being changed is in the
    // right order relative to both $priority_other and the hook whose
    // priority was $priority_new.
    if (in_array($priority_new, $priorities)) {
      $priorities[$index_this] = $priority_other + ($should_be_larger ? 0.5 : -0.5);
      asort($priorities);
      $changed_indexes = array_keys($priorities);
      $priorities = array_combine($changed_indexes, range(1, count($changed_indexes)));
    }
    else {
      $priorities[$index_this] = $priority_new;
      $changed_indexes = [$index_this];
    }
    foreach ($changed_indexes as $index) {
      [$id, $key] = explode('.', $index);
      self::setPriority($container, $id, (int) $key, $priorities[$index]);
    }
  }

  /**
   * Set the priority of a listener.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The container.
   * @param string $class
   *   The name of the class, this is the same as the service id.
   * @param int $key
   *   The key within the tags array of the 'kernel.event_listener' tag for the
   *   hook implementation to be changed.
   * @param int $priority
   *   The new priority.
   *
   * @return void
   */
  public static function setPriority(ContainerBuilder $container, string $class, int $key, int $priority): void {
    $definition = $container->getDefinition($class);
    $tags = $definition->getTags();
    $tags['kernel.event_listener'][$key]['priority'] = $priority;
    $definition->setTags($tags);
  }

}
