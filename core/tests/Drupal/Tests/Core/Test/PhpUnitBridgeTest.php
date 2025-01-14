<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\deprecation_test\DeprecatedMethod;
use Drupal\Tests\UnitTestCase;
use Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass;

/**
 * Test how unit tests interact with deprecation errors.
 *
 * @group Test
 * @group legacy
 */
class PhpUnitBridgeTest extends UnitTestCase {

  /**
   * Tests class-level deprecation.
   */
  public function testDeprecatedClass(): void {
    $this->expectDeprecation('Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass is deprecated.');
    $deprecated = new FixtureDeprecatedClass();
    $this->assertEquals('test', $deprecated->testFunction());
  }

  /**
   * Tests function deprecation.
   */
  public function testDeprecatedFunction(): void {
    $this->assertEquals('known_return_value', DeprecatedMethod::methodDeprecated());
  }

}
