<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Core\Test;

use Drupal\Core\Url;
use Drupal\deprecation_test\DeprecatedMethod;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Drupal's extension to manage code deprecation.
 *
 * @group Test
 * @group legacy
 */
class PhpUnitBridgeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['deprecation_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests deprecation message from deprecation_test_function().
   */
  public function testSilencedError(): void {
    $this->expectDeprecation('This is the deprecation message for \Drupal\deprecation_test\DeprecatedMethod::methodDeprecated().');
    $this->assertEquals('known_return_value', DeprecatedMethod::methodDeprecated());
  }

  /**
   * Tests deprecation message from deprecated route.
   */
  public function testErrorOnSiteUnderTest(): void {
    $this->expectDeprecation('This is the deprecation message for \Drupal\deprecation_test\DeprecatedMethod::methodDeprecated().');
    $this->drupalGet(Url::fromRoute('deprecation_test.route'));
  }

}
