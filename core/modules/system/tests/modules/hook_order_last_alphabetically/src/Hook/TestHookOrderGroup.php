<?php

declare(strict_types=1);

namespace Drupal\hook_order_last_alphabetically\Hook;

use Drupal\Core\Hook\Attribute\Hook;

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
class TestHookOrderGroup {

  /**
   * This pair tests OrderAfter with Group.
   */
  #[Hook('custom_hook_extra_types2_alter')]
  public static function customHookExtraTypes(): void {
    $GLOBALS['HookOrderGroupExtraTypes'] = 'HookOrderGroupExtraTypes';
  }

}
