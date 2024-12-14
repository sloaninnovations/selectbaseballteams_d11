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
   * @param \Drupal\Core\Hook\Attribute\Hook $hook
   *   The hook attribute to order.
   * @param string $class
   *   The class the hook is in.
   */
  public function set(Hook $hook, string $class): static;

}
