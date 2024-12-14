<?php

declare(strict_types=1);

namespace Drupal\hook_second_order_first_alphabetically\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\HookAfter;

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
 * Each method pair tests one hook ordering attribute.
 */
class TestHookAfterClassMethod {

  /**
   * This pair tests #[HookAfter] with a passed class and method.
   */
  #[HookAfter(['hook_second_order_last_alphabetically', 'TestHookAfterClassMethod::hookAfterClassMethod'])]
  #[Hook('custom_hook_test_hook_after_class_method')]
  public static function hookAfterClassMethod(): void {
    $GLOBALS['HookAfterClassMethod'] = 'HookAfterMethod';
  }

}
