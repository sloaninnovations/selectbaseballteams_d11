<?php

declare(strict_types=1);

namespace Drupal\hook_order_first_alphabetically1\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\HookLast;

/**
 * Hook implementations for hook_order_first_alphabetically.
 */
class FirstAlphabeticallyHooks1 {

  /**
   * After LastAlphabeticallyHooks1::cacheFlush1
   */
  #[Hook('cache_flush')]
  public static function cacheFlush1(): void {
    if(!isset($GLOBALS['HookFirst'])) {
      $GLOBALS['HookOutOfOrderTestingFirst'] = 'HookOutOfOrderTestingFirst';
    }
    $GLOBALS['HookRanTestingFirst'] = 'HookRanTestingFirst';
  }

  /**
   * After LastAlphabeticallyHooks1::cacheFlush2
   */
  #[HookLast]
  #[Hook('cache_flush')]
  public static function cacheFlush2(): void {
    $GLOBALS['HookLast'] = 'HookLast';
  }

}
