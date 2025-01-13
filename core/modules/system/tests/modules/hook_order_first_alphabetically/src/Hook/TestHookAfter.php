<?php

declare(strict_types=1);

namespace Drupal\hook_order_first_alphabetically\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\OrderAfter;

/**
 * Hook implementations for verifying ordering hooks by attributes.
 *
 * We must ensure that the order of the modules is expected and then change
 * the order that the hooks are run in order to verify. This module
 * comes in a pair first alphabetically and last alphabetically.
 *
 * In the normal order a hook implemented by first alphabetically would run
 * before the same hook in last alphabetically.
 *
 * Each method pair tests one hook ordering permutation.
 */
class TestHookAfter {

  /**
   * This pair tests OrderAfter.
   */
  #[Hook('custom_hook_test_hook_after', order: new OrderAfter(['hook_order_last_alphabetically']))]
  public static function hookAfter(): void {
    $GLOBALS['HookAfter'] = 'HookAfter';
  }

}
