<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Attribute for marking which specific implementations to group.
 *
 * This allows hook ordering to handle extra types such as ordering form_alter
 * relative to hook_form_FORM_ID_alter.
 *
 * @section sec_backwards_compatibility Backwards-compatibility
 *
 * To allow hook implementations to work on older versions of Drupal as well,
 * keep the hook_module_implements_alter implementation and add the appropriate
 * combination of #[HookFirst], #[HookLast], #[HookBefore], #[HookAfter], and
 * #[HookOrderGroup] attributes. Then ensure you have added #[LegacyHook] to
 * the hook_module_implements_alter() implementation.
 *
 * See \Drupal\Core\Hook\Attribute\LegacyHook for additional information.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class HookOrderGroup {

  /**
   * Constructs a core/lib/Drupal/Core/Hook/Attribute/HookOrderGroup.php attribute object.
   *
   * @param array $group
   *   A list of hooks to sort together. For example, if a method implementing
   *   form_BASE_FORM_ID_alter wants to sort itself relative to some
   *   implementations of form_FORM_ID_alter then this would contain those.
   *   See Ckeditor5::formFilterFormatFormAlter() for example.
   */
  public function __construct(
    public array $group,
  ) {}

}
