<?php

declare(strict_types=1);

namespace Drupal\hook_order_last_alphabetically5\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for hook_order_last_alphabetically.
 */
class LastAlphabeticallyHooks5 {

  /**
   * Before FirstAlphabeticallyHooks5::cacheFlush.
   */
  #[Hook('cache_flush')]
  public static function cacheFlush(): void {
    if (isset($GLOBALS['HookLast'])) {
      $GLOBALS['HookOutOfOrderTestingLast'] = 'HookOutOfOrderTestingLast';
    }
    $GLOBALS['HookRanTestingLast'] = 'HookRanTestingLast';
  }

}
