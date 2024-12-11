<?php

declare(strict_types=1);

namespace Drupal\hook_order_first_alphabetically3\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for hook_order_first_alphabetically.
 */
class FirstAlphabeticallyHooks3 {

  /**
   * After LastAlphabeticallyHooks3::cacheFlush.
   */
  #[Hook('cache_flush')]
  public static function cacheFlush(): void {
    if (!isset($GLOBALS['HookBefore'])) {
      $GLOBALS['HookOutOfOrderTestingBefore'] = 'HookOutOfOrderTestingBefore';
    }
    $GLOBALS['HookRanTestingBefore'] = 'HookRanTestingBefore';
  }

}
