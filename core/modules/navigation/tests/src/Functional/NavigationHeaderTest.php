<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for navigation header section.
 *
 * @group navigation
 */
class NavigationHeaderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['navigation', 'navigation_test', 'test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->createUser([
      'access navigation',
    ]));
  }

  /**
   * Tests behavior of promoted section hooks.
   */
  public function testNavigationPromoted(): void {
    $test_page_url = Url::fromRoute('test_page_test.test_page');
    $this->drupalGet($test_page_url);
    $this->assertSession()->elementNotExists('css', '.admin-toolbar__promoted');
    \Drupal::state()->set('navigation_promoted', 1);
    drupal_flush_all_caches();
    $this->drupalGet($test_page_url);
    $this->assertSession()->elementTextContains('css', '.admin-toolbar__promoted', 'foobarbaz');
    \Drupal::state()->set('navigation_promoted_alter', 1);
    drupal_flush_all_caches();
    $this->drupalGet($test_page_url);
    $this->assertSession()->elementTextContains('css', '.admin-toolbar__promoted', 'baznew bar');
  }

}
