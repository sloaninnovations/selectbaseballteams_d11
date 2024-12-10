<?php

declare(strict_types=1);

namespace Drupal\hook_order_first_alphabetically2\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\HookAfter;
use Drupal\Core\Hook\Attribute\HookOrderGroup;

/**
 * Hook implementations for hook_order_first_alphabetically.
 */
class FirstAlphabeticallyHooks2 {
  /**
   * After LastAlphabeticallyHooks2::cacheFlush1
   */
  #[HookAfter(['hook_order_last_alphabetically2'])]
  #[HookOrderGroup(['cacheFlush1'])]
  #[Hook('cache_flush')]
  public static function cacheFlush1(): void {
    $GLOBALS['HookAfter'] = 'HookAfter';
  }

  /**
   * After LastAlphabeticallyHooks2::cacheFlush2
   */
  #[Hook('cache_flush')]
  public static function cacheFlush2(): void {
    if(!isset($GLOBALS['HookBefore'])) {
      $GLOBALS['HookOutOfOrderTestingBefore'] = 'HookOutOfOrderTestingBefore';
    }
    $GLOBALS['HookRanTestingBefore'] = 'HookRanTestingBefore';
  }

}
