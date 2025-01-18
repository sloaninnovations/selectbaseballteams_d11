<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Environment;
use PHPUnit\Framework\TestCase;

/**
 * Test PHP Environment helper methods.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Environment
 */
class EnvironmentTest extends TestCase {

  /**
   * Tests \Drupal\Component\Utility\Environment::setTimeLimit().
   *
   * @covers ::setTimeLimit
   * @runInSeparateProcess
   * @testWith [0, 1, 0]
   *           [10, 1, 11]
   *           [1, 10, 10]
   *           [10, 0, 0]
   *
   * @param int $max_execution_time
   *   The max_execution_time to be set in php ini.
   * @param int $time_limit
   *   The required time_limit argument for
   *   \Drupal\Component\Utility\Environment::setTimeLimit().
   * @param bool $expected
   *   The expected return value from
   *   \Drupal\Component\Utility\Environment::setTimeLimit().
   */
  public function testSetTimeLimit(int $max_execution_time, int $time_limit, int $expected): void {
    ini_set('max_execution_time', $max_execution_time);
    Environment::setTimeLimit($time_limit);
    $actual = ini_get('max_execution_time');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests \Drupal\Component\Utility\Environment::checkMemoryLimit().
   *
   * @dataProvider providerTestCheckMemoryLimit
   * @covers ::checkMemoryLimit
   *
   * @param string $required
   *   The required memory argument for
   *   \Drupal\Component\Utility\Environment::checkMemoryLimit().
   * @param string $custom_memory_limit
   *   The custom memory limit argument for
   *   \Drupal\Component\Utility\Environment::checkMemoryLimit().
   * @param bool $expected
   *   The expected return value from
   *   \Drupal\Component\Utility\Environment::checkMemoryLimit().
   */
  public function testCheckMemoryLimit($required, $custom_memory_limit, $expected): void {
    $actual = Environment::checkMemoryLimit($required, $custom_memory_limit);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Provides data for testCheckMemoryLimit().
   *
   * @return array
   *   An array of arrays, each containing the arguments for
   *   \Drupal\Component\Utility\Environment::checkMemoryLimit():
   *   required and memory_limit, and the expected return value.
   */
  public static function providerTestCheckMemoryLimit() {
    return [
      // Minimal amount of memory should be available.
      ['30MB', NULL, TRUE],
      // Test an unlimited memory limit.
      ['9999999999YB', -1, TRUE],
      // Exceed a custom memory limit.
      ['30MB', '16MB', FALSE],
      // Available = required.
      ['30MB', '30MB', TRUE],
    ];
  }

}
