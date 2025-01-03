<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

use Drupal\Component\Annotation\Doctrine\StaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Extension\ProceduralCall;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\Core\Hook\Attribute\OverrideHook;
use Drupal\Core\Hook\Attribute\RemoveHook;
use Drupal\Core\Hook\Attribute\StopProceduralHookScan;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

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
   * A list of attributes for hook implementations.
   *
   * Keys are module, class and method. Values are Hook attributes.
   */
  protected array $moduleHooks = [];

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): array {
    $collector = static::collectAllHookImplementations($container->getParameter('container.modules'), $container);
    $implementations = [];
    $legacyImplementations = [];
    $orderGroups = [];
    $orderAttributes = [];
    $moduleFinder = [];
    $removals = [];
    foreach (array_keys($container->getParameter('container.modules')) as $module) {
      foreach ($collector->moduleHooks[$module] ?? [] as $class => $methods) {
        foreach ($methods as $method => $hooks) {
          foreach ($hooks as $hook) {
            assert($hook instanceof Hook);
            if ($hook instanceof OverrideHook && !class_exists($hook->class, FALSE)) {
              continue;
            }
            if ($hook instanceof RemoveHook) {
              $removals[] = $hook;
              continue;
            }
            $hook->set(class: $class, module: $module, method: $method);
            if ($hook->class !== ProceduralCall::class) {
              self::checkForProceduralOnlyHooks($hook);
            }
            $legacyImplementations[$hook->hook][$hook->module] = '';
            $implementations[$hook->hook][$hook->module][$hook->class][$hook->method] = $hook->method;
            $moduleFinder[$hook->class][$method] = $hook->module;
            if ($hook->order) {
              $orderAttributes[] = $hook;
              if ($hook->order instanceof ComplexOrder && ($group = $hook->order->group)) {
                $group[] = $hook->hook;
                foreach ($group as $extraHook) {
                  $orderGroups[$extraHook] = array_merge($orderGroups[$extraHook] ?? [], $group);
                }
              }
            }
          }
        }
      }
    }
    $orderGroups = array_map('array_unique', $orderGroups);

    foreach ($removals as $hook) {
      unset($legacyImplementations[$hook->hook][$hook->module]);
      unset($implementations[$hook->hook][$hook->module][$hook->class][$hook->method]);
    }

    // @todo investigate whether this if() is needed after ModuleHandler::add()
    // is removed.
    // @see https://www.drupal.org/project/drupal/issues/3481778
    if (count($container->getDefinitions()) > 1) {
      static::registerImplementations($container, $collector, $implementations, $legacyImplementations ?? [], $orderGroups);
      static::reOrderImplementations($container, $orderAttributes, $orderGroups, $implementations, $moduleFinder);
    }
    return $implementations;
  }

  /**
   * Register hook implementations as event listeners.
   *
   * Passes required include and ordering information to module_handler.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container.
   * @param \Drupal\Core\Hook\HookCollectorPass $collector
   *   The collector.
   * @param array $implementations
   *   All implementations.
   * @param array $legacyImplementations
   *   Modules that implement legacy hooks.
   * @param array $orderGroups
   *   Groups of hooks to reorder.
   */
  protected static function registerImplementations(ContainerBuilder $container, HookCollectorPass $collector, array $implementations, array $legacyImplementations, array $orderGroups): void {
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

    foreach ($legacyImplementations as $hook => $moduleImplements) {
      $extraHooks = $orderGroups[$hook] ?? [];
      foreach ($extraHooks as $extraHook) {
        $moduleImplements += $legacyImplementations[$extraHook] ?? [];
      }
      foreach ($collector->moduleImplementsAlters as $alter) {
        $alter($moduleImplements, $hook);
      }
      $legacyImplementations[$hook] = $moduleImplements;
      $priority = 0;
      foreach ($moduleImplements as $module => $v) {
        foreach ($implementations[$hook][$module] ?? [] as $class => $method_hooks) {
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
            $priority = self::addTagToDefinition($definition, $hook, $method, $priority);
          }
        }
        unset($implementations[$hook][$module]);
      }
    }

    $definition = $container->getDefinition('module_handler');
    $definition->setArgument('$groupIncludes', $groupIncludes);
    $definition->setArgument('$orderGroups', $orderGroups);
    $container->setParameter('hook_implementations_map', $map ?? []);
  }

  /**
   * Reorder hook implementations specifying an order.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container.
   * @param array $orderAttributes
   *   All attributes related to ordering.
   * @param array $orderGroups
   *   Groups to order by.
   * @param array $implementations
   *   Hook implementations.
   * @param array $moduleFinder
   *   An array keyed by the class and method of a hook implementation, value
   *   is the module. This is not necessarily the same as the module the class
   *   is in because the implementation might be on behalf of another module.
   */
  protected static function reOrderImplementations(ContainerBuilder $container, array $orderAttributes, array $orderGroups, array $implementations, array $moduleFinder): void {
    $hookPriority = new HookPriority($container);
    foreach ($orderAttributes as $orderAttribute) {
      assert($orderAttribute instanceof Hook);
      // ::process() adds the hook serving as key to the order group so it
      // does not need to be added if there's a group for the hook.
      $hooks = $orderGroups[$orderAttribute->hook] ?? [$orderAttribute->hook];
      $combinedHook = implode(':', $hooks);
      if ($orderAttribute->order instanceof ComplexOrder) {
        // Verify the correct structure of
        // $orderAttribute->order->classesAndMethods and create specifiers
        // for HookPriority::change() while at it.
        $otherSpecifiers = array_map(fn ($pair) => is_array($pair) ? $pair[0] . '::' . $pair[1] : throw new \LogicException('classesAndMethods needs to be an array of arrays'), $orderAttribute->order->classesAndMethods);
        // Collect classes and methods for
        // self::registerComplexHookImplementations().
        $classesAndMethods = $orderAttribute->order->classesAndMethods;
        foreach ($orderAttribute->order->modules as $modules) {
          foreach ($hooks as $hook) {
            foreach ($implementations[$hook][$modules] ?? [] as $class => $methods) {
              foreach ($methods as $method) {
                $classesAndMethods[] = [$class, $method];
                $otherSpecifiers[] = "$class::$method";
              }
            }
          }
        }
        if (count($hooks) > 1) {
          // The hook implementation in $orderAttribute and everything in
          // $classesAndMethods will be ordered relative to each other as if
          // they were implementing a single hook. This needs to be marked on
          // their service definition and added to the
          // hook_implementations_map container parameter.
          $classesAndMethods[] = [$orderAttribute->class, $orderAttribute->method];
          self::registerComplexHookImplementations($container, $classesAndMethods, $moduleFinder, $combinedHook);
        }
      }
      else {
        $otherSpecifiers = NULL;
      }
      $hookPriority->change("drupal_hook.$combinedHook", $orderAttribute, $otherSpecifiers);
    }
  }

  /**
   * Collects all hook implementations.
   *
   * @param array $module_filenames
   *   An associative array. Keys are the module names, values are relevant
   *   info yml file path.
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder|null $container
   *   The container.
   *
   * @return static
   *   A HookCollectorPass instance holding all hook implementations and
   *   include file information.
   *
   * @internal
   *   This method is only used by ModuleHandler.
   *
   * @todo Pass only $container when ModuleHandler::add() is removed
   *   @see https://www.drupal.org/project/drupal/issues/3481778
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
        if ($cached) {
          $class = $cached['class'];
          $attributes = $cached['attributes'];
        }
        else {
          $namespace = preg_replace('#^src/#', "Drupal/$module/", $iterator->getSubPath());
          $class = $namespace . '/' . $fileinfo->getBasename('.php');
          $class = str_replace('/', '\\', $class);
          $attributes = [];
          if (class_exists($class)) {
            $reflectionClass = new \ReflectionClass($class);
            $reflections = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
            $reflections[] = $reflectionClass;
            $attributes = self::getAttributeInstances($attributes, $reflections);
            $hook_file_cache->set($filename, ['class' => $class, 'attributes' => $attributes]);
          }
        }
        $this->moduleHooks[$module][$class] = $attributes;
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
   * Adds a procedural hook implementation.
   *
   * @param \SplFileInfo $fileinfo
   *   The file this procedural implementation is in.
   * @param string $hook
   *   The name of the hook.
   * @param string $module
   *   The module of the hook. Note this might be different from the module the
   *   function is in.
   * @param string $function
   *   The name of function implementing the hook.
   */
  protected function addProceduralImplementation(\SplFileInfo $fileinfo, string $hook, string $module, string $function): void {
    $this->moduleHooks[$module][ProceduralCall::class][$function] = [new Hook($hook, method: $module . '_' . $hook)];
    if ($hook === 'hook_info') {
      $this->hookInfo[] = $function;
    }
    if ($hook === 'module_implements_alter') {
      $this->moduleImplementsAlters[] = $function;
    }
    if ($fileinfo->getExtension() !== 'module') {
      $this->includes[$function] = $fileinfo->getPathname();
    }
  }

  /**
   * This method is only to be used by ModuleHandler.
   *
   * @todo remove when ModuleHandler::add() is removed.
   * @see https://www.drupal.org/project/drupal/issues/3481778
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
   * @todo remove when ModuleHandler::add() is removed.
   * @see https://www.drupal.org/project/drupal/issues/3481778
   *
   * @internal
   */
  public function getImplementations($paths): array {
    $container = new ContainerBuilder();
    $container->setParameter('container.modules', $paths);
    return $this->process($container);
  }

  /**
   * Checks for hooks which can't be supported in classes.
   *
   * @param \Drupal\Core\Hook\Attribute\Hook $hook
   *   The hook to check.
   * @param string $class
   *   The class the hook is implemented on.
   */
  public static function checkForProceduralOnlyHooks(Hook $hook, string $class = ''): void {
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
      if (!$class) {
        $class = $hook->class;
      }
      throw new \LogicException("The hook $hook->hook on class $class does not support attributes and must remain procedural.");
    }
  }

  /**
   * Get attribute instances from class and method reflections.
   *
   * @param array $attributes
   *   The current attributes.
   * @param array $reflections
   *   A list of class and method reflections.
   *
   * @return array
   *   A list of Hook attribute instances.
   */
  protected static function getAttributeInstances(array $attributes, array $reflections): array {
    foreach ($reflections as $reflection) {
      if ($reflection_attributes = $reflection->getAttributes(Hook::class, \ReflectionAttribute::IS_INSTANCEOF)) {
        $method = $reflection instanceof \ReflectionMethod ? $reflection->getName() : '__invoke';
        $attributes[$method] = array_map(fn (\ReflectionAttribute $ra) => $ra->newInstance(), $reflection_attributes);
      }
    }
    return $attributes;
  }

  /**
   * Adds an event listener tag to a service definition.
   *
   * @param \Symfony\Component\DependencyInjection\Definition $definition
   *   The service definition.
   * @param string|int $hook
   *   The name of the hook
   * @param string $method
   *   The method.
   * @param int $priority
   *   The priority.
   *
   * @return int
   *   A new priority, guaranteed to be lower than $priority.
   */
  protected static function addTagToDefinition(Definition $definition, string|int $hook, string $method, int $priority): int {
    $definition->addTag('kernel.event_listener', [
      'event' => "drupal_hook.$hook",
      'method' => $method,
      'priority' => $priority--,
    ]);
    return $priority;
  }

  /**
   * Register complex hook implementations.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container.
   * @param array $classesAndMethods
   *   A list of class-and-method pairs.
   * @param array $moduleFinder
   *   A module finder array, see ::reOrderImplementations() for explanation.
   * @param string $combinedHook
   *   A string made form list of hooks separated by :
   */
  protected static function registerComplexHookImplementations(ContainerBuilder $container, array $classesAndMethods, array $moduleFinder, string $combinedHook): void {
    $map = $container->getParameter('hook_implementations_map');
    $priority = 0;
    foreach ($classesAndMethods as [$class, $method]) {
      // Ordering against not installed modules is possible.
      if (isset($moduleFinder[$class][$method])) {
        $map[$combinedHook][$class][$method] = $moduleFinder[$class][$method];
        $priority = self::addTagToDefinition($container->findDefinition($class), $combinedHook, $method, $priority);
      }
    }
    $container->setParameter('hook_implementations_map', $map);
  }

}
