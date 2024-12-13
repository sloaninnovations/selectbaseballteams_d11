<?php

declare(strict_types=1);

namespace Drupal\hook_order_last_alphabetically4\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for hook_order_last_alphabetically.
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
