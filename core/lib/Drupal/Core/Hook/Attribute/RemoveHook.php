<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RemoveHook extends Hook {

  public function __construct(
    string $hook,
    string $class,
    string $module,
    string $method,
  ) {
    parent::__construct($hook, module: $module, method: $method);
    $this->class = $class;
  }

}
