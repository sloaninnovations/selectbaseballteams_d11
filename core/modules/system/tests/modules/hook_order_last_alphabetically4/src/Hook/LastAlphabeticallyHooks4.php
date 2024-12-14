<?php

declare(strict_types=1);

namespace Drupal\hook_order_last_alphabetically4\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for verifying ordering hooks by attributes.
 *
 * We must ensure that the order of the modules is expected and then change
 * the order that the hooks are run in order to verify. All of these modules
 * come in a pair first alphabetically and last alphabetically.
 *
 * In the normal order a hook implemented by first alphabetically would run
 * before the same hook in last alphabetically.
 *
 * Each pair tests one hook order attribute.
 *
 * This pair tests #[HookOrderGroup].
 * This attribute must be paired with #[HookAfter] or #[HookBefore].
 */
class LastAlphabeticallyHooks4 {

  /**
   * Before FirstAlphabeticallyHooks4::customHookExtraTypes.
   */
  #[Hook('custom_hook_extra_types2_alter')]
  public static function customHookExtraTypes(): void {
    $GLOBALS['HookOrderGroupExtraTypes'] = 'HookOrderGroupExtraTypes';
  }

}
