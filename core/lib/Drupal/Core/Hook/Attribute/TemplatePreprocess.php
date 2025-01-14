<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Attribute for defining a class method as a template_preprocess_HOOK.
 *
 * See \Drupal\Core\Hook\Attribute\Hook for additional information.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class TemplatePreprocess extends Hook {
  /**
   * {@inheritdoc}
   */
  public const string PREFIX = 'preprocess';

  /**
   * Constructs a Hook attribute object.
   *
   * @param string $hook
   *   The short hook name, without the 'hook_' prefix.
   * @param string $method
   *   (optional) The method name. If this attribute is on a method, this
   *   parameter is not required. If this attribute is on a class and this
   *   parameter is omitted, the class must have an __invoke() method, which is
   *   taken as the hook implementation.
   */
  public function __construct(
    public string $hook,
    public string $method = '',
  ) {
    parent::__construct($hook, $method, 'template');
  }

}
