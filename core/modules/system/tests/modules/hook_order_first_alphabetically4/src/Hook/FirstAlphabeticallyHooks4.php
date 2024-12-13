<?php

declare(strict_types=1);

namespace Drupal\hook_order_first_alphabetically4\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\HookAfter;
use Drupal\Core\Hook\Attribute\HookOrderGroup;

/**
 * Hook implementations for hook_order_first_alphabetically.
 */
class FirstAlphabeticallyHooks4 {

  /**
   * After LastAlphabeticallyHooks4::customHookExtraTypes.
   */
  #[HookAfter(['hook_order_last_alphabetically4'])]
  #[HookOrderGroup(['custom_hook_extra_types2_alter'])]
  #[Hook('custom_hook_extra_types1_alter')]
  public static function customHookExtraTypes(): void {
    if (!isset($GLOBALS['HookOrderGroupExtraTypes'])) {
      $GLOBALS['HookOutOfOrderTestingOrderGroupsExtraTypes'] = 'HookOutOfOrderTestingOrderGroupsExtraTypes';
    }
    $GLOBALS['HookRanTestingOrderGroupsExtraTypes'] = 'HookRanTestingOrderGroupsExtraTypes';
  }

}
