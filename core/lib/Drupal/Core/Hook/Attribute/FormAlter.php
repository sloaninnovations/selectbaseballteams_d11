<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Hook attribute for FormAlter.
 *
 * @see hook_form_alter().
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class FormAlter extends Alter {
  /**
   * {@inheritdoc}
   */
  public const string PREFIX = 'form';

  /**
   * Constructs a Hook attribute object.
   *
   * @param string $form_id
   *   The ID of the form that this implementation alters.
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
    string $form_id = '',
    public string $method = '',
    public ?string $module = NULL,
  ) {
    parent::__construct($form_id, $method, $module);
  }

}
