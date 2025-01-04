<?php

declare(strict_types=1);

namespace Drupal\hook_order_first_alphabetically\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\OrderAfter;
use Drupal\Core\Hook\Attribute\OverrideHook;
use Drupal\hook_order_last_alphabetically\Hook\TestHookOverrideHookSecond;

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
class TestHookOverrideHookFirst {

  /**
   * This pair tests OverrideHook.
   */
  #[Hook('custom_hook_override')]
  #[OverrideHook(
    'custom_hook_override',
    class: TestHookOverrideHookSecond::class,
    method: 'customHookOverride',
    order: new OrderAfter(
      classesAndMethods: [[TestHookOverrideHookFirst::class, 'customHookOverride']],
    )
  )]
  public static function customHookOverride(): void {
    // This normally would run first.
    // We override that order in hook_order_second_alphabetically.
    // We override, that order here with OverrideHook.
    $GLOBALS['HookRanTestingOverrideHookFirstAlpha'] = 'HookRanTestingOverrideHookFirstAlpha';
  }

}
