<?php

namespace Drupal\FunctionalTests\Core\Test;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Drupal's integration with Symfony PHPUnit Bridge.
 *
 * @group Test
 * @group legacy
 */
class PhpUnitBridgeTest extends BrowserTestBase {

  protected static $modules = ['deprecation_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests deprecation message from deprecation_test_function().
   *
   * @deprecated in drupal:8.4.0 and is removed from drupal:9.0.0. This is
   *   the deprecation message for deprecated_test_function().
   *
   * @see https://www.drupal.org/project/drupal/issues/2870194
   */
  public function testSilencedError() {
    $this->expectDeprecation('This is the deprecation message for deprecation_test_function().');
    $this->assertEquals('known_return_value', deprecation_test_function());
  }

  /**
   * Tests deprecation message from deprecated route.
   */
  public function testErrorOnSiteUnderTest() {
    $this->expectDeprecation('This is the deprecation message for deprecation_test_function().');
    $this->drupalGet(Url::fromRoute('deprecation_test.route'));
  }

}
