<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Interface for classes that manage hook ordering.
 */
interface HookOrderInterface {

  /**
   * Set the properties on the attributes using this class.
   *
   * @param string $hook
   *   The hook to order.
   * @param string $class
   *   The class the hook is in.
   * @param string $method
   *   The method of the hook.
   * @param string $module
   *   The module of the hook.
   */
  public function set(string $hook, string $class, string $method, string $module): static;

}
