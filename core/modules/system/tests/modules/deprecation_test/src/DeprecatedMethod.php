<?php

declare(strict_types=1);

namespace Drupal\deprecation_test;

/**
 * Class with deprecated method.
 *
 * @see \Drupal\FunctionalTests\Core\Test\PhpUnitBridgeTest
 * @see \Drupal\KernelTests\Core\Test\PhpUnitBridgeTest::testDeprecatedFunction()
 */
class DeprecatedMethod {

  /**
   * A deprecated method.
   *
   * @return string
   *   A known return value of 'known_return_value'.
   *
   * @deprecated in drupal:8.4.0 and is removed from drupal:9.0.0. This is
   *   the deprecation message for DeprecatedMethod::methodDeprecated().
   *
   * @see https://www.drupal.org/project/drupal/issues/2870194
   */
  public static function methodDeprecated(): string {
    // phpcs:ignore Drupal.Semantics.FunctionTriggerError
    @trigger_error('This is the deprecation message for \Drupal\deprecation_test\DeprecatedMethod::methodDeprecated().', E_USER_DEPRECATED);
    return 'known_return_value';
  }

}
