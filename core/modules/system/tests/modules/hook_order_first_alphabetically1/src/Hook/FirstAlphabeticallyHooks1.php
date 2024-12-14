<?php

declare(strict_types=1);

namespace Drupal\hook_order_first_alphabetically1\Hook;

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
 * This pair tests #[HookFirst].
 */
class FirstAlphabeticallyHooks1 {

  /**
   * After LastAlphabeticallyHooks1::cacheFlush.
   */
  #[Hook('cache_flush')]
  public static function cacheFlush(): void {
    // This should be run after so HookFirst should not be set.
    if (!isset($GLOBALS['HookFirst'])) {
      $GLOBALS['HookOutOfOrderTestingFirst'] = 'HookOutOfOrderTestingFirst';
    }
    $GLOBALS['HookRanTestingFirst'] = 'HookRanTestingFirst';
  }

}
