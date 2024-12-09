<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Attribute for marking that a hook should be changed.
 *
 * This allows you to ensure the hook is executed after
 * a specific hook in another module.
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
class HookAfter {

  /**
   * Constructs a core/lib/Drupal/Core/Hook/Attribute/HookAfter.php attribute object.
   *
   * @param string $module
   *   The module this implementation should run before.
   */
  public function __construct(
    public string $module,
  ) {}

}
