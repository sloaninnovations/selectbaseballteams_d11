<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\TestTools\Extension\DeprecationBridge\ExpectDeprecationTrait;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Ensures test coverage for deprecation testing.
 *
 * @group Test
 */
#[Group('Test')]
#[IgnoreDeprecations]
class ExpectDeprecationTest extends TestCase {

  use PhpUnitCompatibilityTrait;
  use ExpectDeprecationTrait;

  /**
   * Tests expectDeprecation.
   */
  public function testExpectDeprecation(): void {
    $this->expectDeprecation('Test deprecation');
    // phpcs:ignore Drupal.Semantics.FunctionTriggerError
    @trigger_error('Test deprecation', E_USER_DEPRECATED);
  }

  /**
   * Tests expectDeprecation in isolated test.
   */
  #[RunInSeparateProcess]
  #[PreserveGlobalState(FALSE)]
  public function testExpectDeprecationInIsolation(): void {
    $this->expectDeprecation('Test isolated deprecation');
    // phpcs:ignore Drupal.Semantics.FunctionTriggerError
    @trigger_error('Test isolated deprecation', E_USER_DEPRECATED);
  }

}
