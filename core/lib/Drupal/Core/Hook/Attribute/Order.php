<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

class Order {

  public function __construct(
    public OrderType $type,
    public array $modules = [],
    public array $classesAndMethods = [],
    public array $group = [],
  ) {
    if (!$this->modules && !$this->classesAndMethods) {
      throw new \LogicException('Order must provide elements to order against.');
    }
  }

}
