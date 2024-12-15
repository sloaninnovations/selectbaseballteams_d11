<?php

declare(strict_types=1);

namespace Drupal\hook_second_order_first_alphabetically\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\Order;
use Drupal\Core\Hook\Attribute\OrderType;
use Drupal\hook_second_order_last_alphabetically\Hook\TestHookAfterClassMethod as TestHookAfterClassMethodForAfter;

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
   * This pair tests OrderType::After with a passed class and method.
   */
  #[Hook('custom_hook_test_hook_after_class_method',
    order: new Order(
      type: OrderType::After,
      classesAndMethods: [TestHookAfterClassMethodForAfter::class, 'hookAfterClassMethod'],
    )
  )]
  public static function hookAfterClassMethod(): void {
    $GLOBALS['HookAfterClassMethod'] = 'HookAfterMethod';
  }

}
