<?php

declare(strict_types=1);

namespace Drupal\hook_order_last_alphabetically2\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for hook_order_last_alphabetically.
 */
class LastAlphabeticallyHooks2 {

  /**
   * Before FirstAlphabeticallyHooks2::cacheFlush1.
   */
  #[Hook('cache_flush')]
  public static function cacheFlush1(): void {
    // This should be run before so HookAfter should not be set.
    if (isset($GLOBALS['HookAfter'])) {
      $GLOBALS['HookOutOfOrderTestingAfter'] = 'HookOutOfOrderTestingAfter';
    }
    $GLOBALS['HookRanTestingAfter'] = 'HookRanTestingAfter';
  }

}
