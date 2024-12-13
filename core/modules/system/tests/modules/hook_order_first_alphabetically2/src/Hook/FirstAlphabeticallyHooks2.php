<?php

declare(strict_types=1);

namespace Drupal\hook_order_first_alphabetically2\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\HookAfter;

/**
 * Hook implementations for hook_order_first_alphabetically.
 */
class FirstAlphabeticallyHooks2 {

  /**
   * After LastAlphabeticallyHooks2::cacheFlush.
   */
  #[HookAfter(['hook_order_last_alphabetically2'])]
  #[Hook('cache_flush')]
  public static function cacheFlush(): void {
    $GLOBALS['HookAfter'] = 'HookAfter';
  }

}
