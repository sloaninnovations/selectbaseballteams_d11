<?php

declare(strict_types=1);

namespace Drupal\hook_order_last_alphabetically3\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\HookBefore;
use Drupal\Core\Hook\Attribute\HookOrderGroup;

/**
 * Hook implementations for hook_order_last_alphabetically3.
 */
class LastAlphabeticallyHooks3 {

  /**
   * Before FirstAlphabeticallyHooks3::cacheFlush.
   */
  #[HookBefore(['hook_order_first_alphabetically3'])]
  #[HookOrderGroup(['cache_flush'])]
  #[Hook('cache_flush')]
  public static function cacheFlush(): void {
    $GLOBALS['HookBefore'] = 'HookBefore';
  }

}
