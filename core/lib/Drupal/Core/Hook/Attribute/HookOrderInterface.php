<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

interface HookOrderInterface {

  public function getOrderAction(string $hook, string $class, string $method): \Closure;

}
