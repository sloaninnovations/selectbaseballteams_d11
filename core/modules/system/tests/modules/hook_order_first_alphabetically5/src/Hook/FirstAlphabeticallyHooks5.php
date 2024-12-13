<?php

declare(strict_types=1);

namespace Drupal\hook_order_first_alphabetically5\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\HookLast;

/**
 * Hook implementations for hook_order_first_alphabetically.
 */
class FirstAlphabeticallyHooks5 {

  /**
   * After LastAlphabeticallyHooks5::cacheFlush.
   */
  #[HookLast]
  #[Hook('cache_flush')]
  public static function cacheFlush(): void {
    $GLOBALS['HookLast'] = 'HookLast';
  }

}
