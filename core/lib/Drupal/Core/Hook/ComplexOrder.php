<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

/**
 * Set this implementation to be before or after others.
 */
abstract readonly class ComplexOrder {

  /**
   * Whether the priority of this hook should be larger than others.
   */
  const bool VALUE = FALSE;

  /**
   * Whether the priority of this hook should be larger than others.
   *
   * This is fixed to the constant ::VALUE, it simplifies ordering by ensuring
   * ComplexOrder and Order types both have a value property.
   *
   * @var bool
   */
  public bool $value;

  /**
   * Constructs a ComplexOrder object.
   *
   * @param array $modules
   *   A list of modules.
   * @param array $classesAndMethods
   *   A list of classes and methods, for example:
   *   @code
   *     [
   *       [Foo::class, 'someMethod'],
   *       [Bar::class, 'someOtherMethod'],
   *     ]
   *   @endcode
   * @param array $group
   *   A list of hooks to be ordered together. Ordering by attributes happens
   *   at build time by setting up the order of the listeners of a hook
   *   correctly. However, ModuleHandlerInterface::alter() can be called with
   *   multiple hooks runtime. If the hook defined on this method/class
   *   requires ordering relative to other such hooks then this parameter can
   *   be used to order relative to implementations of all hooks in the group.
   *   Include all alter hooks to be ordered against in the group even if no
   *   single alter() call includes all of them. For example, this can be used
   *   to order a hook_form_BASE_FORM_ID_alter() implementation relative to
   *   multiple hook_form_FORM_ID_alter() implementations as
   *   Drupal\ckeditor5\Hook\Ckeditor5Hooks::formFilterFormatFormAlter() does.
   */
  public function __construct(
    public array $modules = [],
    public array $classesAndMethods = [],
    public array $group = [],
  ) {
    if (!$this->modules && !$this->classesAndMethods) {
      throw new \LogicException('Order must provide either modules or class-method pairs to order against.');
    }
    $this->value = static::VALUE;
  }

}
