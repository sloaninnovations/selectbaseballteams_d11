<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Attribute for removing an implementation.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RemoveHook extends Hook {

  /**
   * Constructs a Hook attribute object.
   *
   * @param string $hook
   *   The short hook name, without the 'hook_' prefix.
   * @param string $class
   *   The class the implementation to remove is in.
   * @param string $method
   *   The method name of the implementation to remove.
   */
  public function __construct(
    string $hook,
    string $class,
    string $method,
  ) {
    parent::__construct($hook, method: $method);
    $this->class = $class;
  }

}
