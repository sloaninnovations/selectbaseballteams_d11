<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

use Drupal\Core\Hook\ComplexOrder;
use Drupal\Core\Hook\Order;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class OverrideHook extends Hook {

  public function __construct(
    string $hook,
    string $class,
    string $module,
    string $method,
    Order|ComplexOrder $order,
  ) {
    parent::__construct($hook, method: $method, module: $module, order: $order);
    $this->class = $class;
  }

}
