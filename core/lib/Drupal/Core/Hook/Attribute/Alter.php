<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Hook attribute for Alter hooks.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Alter extends Hook {
  /**
   * {@inheritdoc}
   */
  public const string SUFFIX = 'alter';

  /**
   * Constructs an Alter attribute object.
   *
   * @param string $hook
   *   The short hook name being altered, without the 'hook_' prefix.
   * @param string $method
   *   (optional) The method name. If this attribute is on a method, this
   *   parameter is not required. If this attribute is on a class and this
   *   parameter is omitted, the class must have an __invoke() method, which is
   *   taken as the hook implementation.
   * @param string|null $module
   *   (optional) The module this implementation is for. This allows one module to
   *   implement a hook on behalf of another module. Defaults to the module the
   *   implementation is in.
   */
  public function __construct(
    public string $hook,
    public string $method = '',
    public ?string $module = NULL,
  ) {
    parent::__construct($hook, $method, $module);
  }

}
