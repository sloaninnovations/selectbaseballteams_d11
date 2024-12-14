<?php

declare(strict_types=1);

namespace Drupal\hook_order_last_alphabetically2\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for verifying ordering hooks by attributes.
 *
 * We must ensure that the order of the modules is expected and then change
 * the order that the hooks are run in order to verify. All of these modules
 * come in a pair first alphabetically and last alphabetically.
 *
 * In the normal order a hook implemented by first alphabetically would run
 * before the same hook in last alphabetically.
 *
 * Each pair tests one hook order attribute.
 *
 * This pair tests #[HookAfter].
 */
class LastAlphabeticallyHooks2 {

  /**
   * Before FirstAlphabeticallyHooks2::cacheFlush.
   */
  #[Hook('cache_flush')]
  public static function cacheFlush(): void {
    // This should be run before so HookAfter should not be set.
    if (isset($GLOBALS['HookAfter'])) {
      $GLOBALS['HookOutOfOrderTestingAfter'] = 'HookOutOfOrderTestingAfter';
    }
    $GLOBALS['HookRanTestingAfter'] = 'HookRanTestingAfter';
  }

}
