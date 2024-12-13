<?php

declare(strict_types=1);

namespace Drupal\hook_order_last_alphabetically1\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\HookFirst;
use Drupal\Core\Hook\Attribute\HookOrderGroup;

/**
 * Hook implementations for hook_order_last_alphabetically.
 */
class LastAlphabeticallyHooks1 {

  /**
   * Before FirstAlphabeticallyHooks1::cacheFlush.
   */
  #[HookFirst]
  #[Hook('cache_flush')]
  #[HookOrderGroup(['cache_flush'])]
  public static function cacheFlush(): void {
    $GLOBALS['HookFirst'] = 'HookFirst';
  }

}
