<?php

declare(strict_types=1);

namespace Drupal\hook_order_first_alphabetically1\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for hook_order_first_alphabetically.
 */
class FirstAlphabeticallyHooks1 {

  /**
   * After LastAlphabeticallyHooks1::cacheFlush.
   */
  #[Hook('cache_flush')]
  public static function cacheFlush(): void {
    if (!isset($GLOBALS['HookFirst'])) {
      $GLOBALS['HookOutOfOrderTestingFirst'] = 'HookOutOfOrderTestingFirst';
    }
    $GLOBALS['HookRanTestingFirst'] = 'HookRanTestingFirst';
  }
}
