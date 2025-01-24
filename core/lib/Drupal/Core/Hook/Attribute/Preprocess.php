<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Attribute for defining a class method as a preprocess function.
 *
 * See \Drupal\Core\Hook\Attribute\Hook for additional information.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Preprocess extends Hook {
  /**
   * {@inheritdoc}
   */
  public const string PREFIX = 'preprocess';

  /**
   * Constructs a Preprocess hook_preprocess_HOOK.
   *
   * Pass no arguments for hook_preprocess #[Preprocess]
   *
   * @param string $hook
   *   The short hook name, without the 'hook_' prefix.
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
    public string $hook = '',
    public string $method = '',
    public ?string $module = NULL,
  ) {
    parent::__construct($hook, $method, $module);
  }

}
