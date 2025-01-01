<?php

declare(strict_types=1);

namespace Drupal\hook_test_remove\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Add a hook here, then remove it with another attribute.
 */
class TestHookRemove {

  /**
   * This hook should not be run because the next hook replaces it.
   */
  #[Hook('custom_hook1')]
  public static function hookDoNotRun(): void {
    $GLOBALS['HookShouldNotRunTestRemove'] = 'HookShouldNotRunTestRemove';
  }

  /**
   * This hook should run and prevent custom_hook1.
   */
  #[Hook('custom_hook2', remove: ['hook_test_remove' => ['custom_hook1']])]
  public static function hookDoRun(): void {
    $GLOBALS['HookShouldRunTestRemove'] = 'HookShouldRunTestRemove';
  }

}
