<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

use Drupal\Core\Hook\ComplexOrder;
use Drupal\Core\Hook\Order;

/**
 * Attribute for overriding the order of another Hook.
 *
 * When another hook needs to be ordered provide an OverrideHook attribute
 * that specifies the new ordering attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class OverrideHook extends Hook {

  /**
   * Constructs a Hook attribute object.
   *
   * @param string $hook
   *   The short hook name, without the 'hook_' prefix.
   * @param string $class
   *   The class the implementation to modify is in. This allows one module to
   *   affect the order of another module's hook.
   * @param string $method
   *   The method name of the implementation to modify.
   * @param \Drupal\Core\Hook\Order|\Drupal\Core\Hook\ComplexOrder $order
   *   Set the order of the implementation.
   */
  public function __construct(
    string $hook,
    string $class,
    string $method,
    Order|ComplexOrder $order,
  ) {
    parent::__construct($hook, method: $method, order: $order);
    $this->class = $class;
  }

}
