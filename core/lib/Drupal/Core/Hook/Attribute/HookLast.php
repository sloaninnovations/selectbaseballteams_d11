<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

use Drupal\Core\Hook\HookPriority;

/**
 * Attribute for marking that a hook should be executed last.
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
class HookLast implements HookOrderInterface {

  /**
   * Constructs a core/lib/Drupal/Core/Hook/Attribute/HookLast.php attribute object.
   */
  public function __construct() {}

  public function getOrderAction(string $hook, string $class, string $method): \Closure {
    return fn(HookPriority $hookPriority) => $hookPriority->change($hook, "$class::$method", FALSE);
  }

}
