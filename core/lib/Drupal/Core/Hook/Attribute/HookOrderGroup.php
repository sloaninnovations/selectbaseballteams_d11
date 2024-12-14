<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * List of alter hooks called together.
 *
 * Ordering by attributes happens at build time by setting up the order of
 * the listeners of a hook correctly. However, ModuleHandlerInterface::alter()
 * can be called with multiple hooks runtime. If the hook defined on this
 * method/class requires ordering relative to other such hooks then this
 * attribute can be used to order relative to implementations of all hooks in
 * the group. Include all alter hooks to be ordered against in the group even
 * if no single alter() call includes all of them. For example, this can be
 * used to order a hook_form_BASE_FORM_ID_alter() implementation relative to
 * multiple hook_form_FORM_ID_alter() implementations as
 * Drupal\ckeditor5\Hook\Ckeditor5Hooks::formFilterFormatFormAlter() does.
 *
 * @section sec_backwards_compatibility Backwards-compatibility
 *
 * To allow hook implementations to work on older versions of Drupal as well,
 * keep the hook_module_implements_alter() implementation and execute the
 * same ordering as prescribed by the hook order attributes. Then add
 * #[LegacyHook] to the hook_module_implements_alter() implementation so it
 * only gets executed in older Drupal versions.
 *
 * See \Drupal\Core\Hook\Attribute\LegacyHook for additional information.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class HookOrderGroup {

  /**
   * Constructs a HookOrderGroup attribute object.
   *
   * @param array $group
   *   A list of hooks to sort together.
   */
  public function __construct(
    public array $group,
  ) {}

}
