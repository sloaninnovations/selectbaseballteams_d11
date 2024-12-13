<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

use Drupal\Core\Hook\Attribute\HookOrderBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class HookPriority {

  public function __construct(protected ContainerBuilder $container) {}

  /**
   * Change the priority of a hook implementation.
   *
   * @param array $hooks
   *   The name of the hook.
   * @param \Drupal\Core\Hook\Attribute\HookOrderBase $attribute
   *   The order attribute.
   * @param array|null $others
   *   Other hook implementations to compare to, if any. The array is a list of
   *   arrays containing a class, a method and module.
   */
  public function change(array $hooks, HookOrderBase $attribute, ?array $others = NULL): void {
    $class_and_method = "$attribute->class::$attribute->method";
    if ($others) {
      $other_specifiers = array_map(fn ($pair) => $pair[0] . '::' . $pair[1], $others);
    }
    if (count($hooks) > 1) {
      $map = $this->container->getParameter('hook_implementations_map');
      krsort($hooks);
      $combinedHookTag = implode(':', $hooks);
      $event = "drupal_hook.$combinedHookTag";
      $data = $others;
      $data[] = [$attribute->class, $attribute->method, $attribute->module];
      $priority = 0;
      foreach ($data as [$class, $method, $module]) {
        $definition = $this->container->findDefinition($class);
        $definition->addTag('kernel.event_listener', [
          'event' => "drupal_hook.$combinedHookTag",
          'method' => $method,
          'priority' => $priority--,
        ]);
        $map[$combinedHookTag][$class][$method] = $module;
      }
      $this->container->setParameter('hook_implementations_map', $map);
    }
    else {
      $event = 'drupal_hook.' . reset($hooks);
    }
    foreach ($this->container->findTaggedServiceIds('kernel.event_listener') as $id => $tags) {
      foreach ($tags as $key => $tag) {
        if ($tag['event'] === $event) {
          $index = "$id.$key";
          $priority = $tag['priority'];
          // Symfony documents event listener priorities to be integers,
          // HookCollectorPass sets them to be integers, ::set() only
          // accepts integers.
          assert(is_int($priority));
          $priorities[$index] = $priority;
          $specifier = "$id::" . $tag['method'];
          if ($class_and_method === $specifier) {
            $index_this = $index;
          }
          // $others is defined for before and after, for these compare only
          // the priority of those. For first and last the priority of every
          // other hook matters.
          elseif (!isset($other_specifiers) || in_array($specifier, $other_specifiers)) {
            $priorities_other[$specifier] = $priority;
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
    $priority_other = $attribute->shouldBeLarger ? max($priorities_other) : min($priorities_other);
    // If the order is correct there is nothing to do. If the two priorities
    // are the same then the order is undefined and so it can't be correct.
    // If they are not the same and $priority_this is already larger exactly
    // when $attribute->shouldBeLarger says then it's the correct order.
    if ($priority_this !== $priority_other && ($attribute->shouldBeLarger === ($priority_this > $priority_other))) {
      return;
    }
    $priority_new = $priority_other + ($attribute->shouldBeLarger ? 1 : -1);
    // For first and last this new priority is already larger/smaller
    // than all existing priorities but for before / after it might belong to
    // an already existing hook. In this case set the new priority temporarily
    // to be halfway between $priority_other and $priority_new then give all
    // hook implementations new, integer priorities keeping this new order.
    // This ensures the hook implementation being changed is in the right order
    // relative to both $priority_other and the hook whose priority was
    // $priority_new.
    if (in_array($priority_new, $priorities)) {
      $priorities[$index_this] = $priority_other + ($attribute->shouldBeLarger ? 0.5 : -0.5);
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
      $this->set($id, (int) $key, $priorities[$index]);
    }
  }

  /**
   * Set the priority of a listener.
   *
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
  public function set(string $class, int $key, int $priority): void {
    $definition = $this->container->getDefinition($class);
    $tags = $definition->getTags();
    $tags['kernel.event_listener'][$key]['priority'] = $priority;
    $definition->setTags($tags);
  }

}
