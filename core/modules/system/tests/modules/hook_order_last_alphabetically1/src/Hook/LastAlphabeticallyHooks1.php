<?php

declare(strict_types=1);

namespace Drupal\hook_order_last_alphabetically1\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\HookFirst;

/**
 * Hook implementations for hook_order_last_alphabetically.
 */
class LastAlphabeticallyHooks1 {

  /**
   * Before FirstAlphabeticallyHooks1::cacheFlush1.
   */
  #[HookFirst]
  #[Hook('cache_flush')]
  public static function cacheFlush1(): void {
    $GLOBALS['HookFirst'] = 'HookFirst';
  }

  /**
   * Before FirstAlphabeticallyHooks1::cacheFlush2.
   */
  #[Hook('cache_flush')]
  public static function cacheFlush2(): void {
    if (isset($GLOBALS['HookLast'])) {
      $GLOBALS['HookOutOfOrderTestingLast'] = 'HookOutOfOrderTestingLast';
    }
    $GLOBALS['HookRanTestingLast'] = 'HookRanTestingLast';
  }

  /**
   * Before FirstAlphabeticallyHooks::cacheFlush3.
   */
  #[Hook('cache_flush')]
  public static function cacheFlush3(): void {
    if (isset($GLOBALS['HookLast'])) {
      $GLOBALS['HookOutOfOrderTestingLast'] = 'HookOutOfOrderTestingLast';
    }
    $GLOBALS['HookRanTestingLast'] = 'HookRanTestingLast';
  }

}
