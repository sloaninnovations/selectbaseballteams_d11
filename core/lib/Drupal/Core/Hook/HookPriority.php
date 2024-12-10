<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class HookPriority {

  public function __construct(protected ContainerBuilder $container, protected array $orderGroups) {}

  /**
   * Change the priority of a hook implementation.
   *
   * @param string $hook
   *   The name of the hook.
   * @param string $class_and_method
   *   Class and method separated by :: containing the hook implementation which
   *   should be changed.
   * @param bool $should_be_larger
   *   TRUE for before/first, FALSE for after/last. Larger priority listeners
   *   fire first.
   * @param array|null $others
   *   Other hook implementations to compare to, if any. The array is a list of
   *   strings containing a class and method separated by ::.
   *
   * @return void
   */
  public function change(string $hook, string $class_and_method, bool $should_be_larger, ?array $others = NULL): void {
    $events = ["drupal_hook.$hook"];
    foreach ($this->orderGroups as $group) {
      if (in_array($hook, $group)) {
        foreach ($group as $alsoHook) {
          $events[] = "drupal_hook.$alsoHook";
        }
      }
    }
    $events = array_unique($events);
    foreach ($this->container->findTaggedServiceIds('kernel.event_listener') as $id => $attributes) {
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
          // $others is specified for before and after, for these compare only
          // the priority of those. For first and last the priority of every
          // other hook matters.
          elseif (!isset($others) || in_array($specifier, $others)) {
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
    // For first and last this new priority is already larger/smaller
    // than all existing priorities but for before / after it might belong to
    // an already existing hook. In this case set the new priority temporarily
    // to be halfway between $priority_other and $priority_new then give all
    // hook implementations new, integer priorities keeping this new order.
    // This ensures the hook implementation being changed is in the right order
    // relative to both $priority_other and the hook whose priority was
    // $priority_new.
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
      self::set($this->container, $id, (int) $key, $priorities[$index]);
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
  public static function set(ContainerBuilder $container, string $class, int $key, int $priority): void {
    $definition = $container->getDefinition($class);
    $tags = $definition->getTags();
    $tags['kernel.event_listener'][$key]['priority'] = $priority;
    $definition->setTags($tags);
  }

}
