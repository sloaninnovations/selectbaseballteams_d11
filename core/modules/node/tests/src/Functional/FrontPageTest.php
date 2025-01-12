<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Demonstrating config issue.
 *
 * @group node
 */
class FrontPageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page']);

    $this->drupalCreateNode(['type' => 'page', 'title' => 'Test homepage',]);
  }

  /**
   * Tests setting the front page, and then getting the front page.
   *
   * This test fails, but it should be successful.
   */
  public function testFrontpageSet(): void {
    $this->config('system.site')->set('page.front', '/node/1')->save();

    $this->drupalGet('<front>');
    // Title IS "Log in | Drupal".
    $this->assertSession()->titleEquals('Log in | Drupal');
    // Title SHOULD BE "Test homepage | Drupal".
    $this->assertSession()->titleEquals('Test homepage | Drupal');
  }

  /**
   * Tests setting the front page, clearing data cache, and getting front page.
   * 
   * This test will succeed, but this cache clear should not be necessary.
   */
  public function testFrontpageSetWithCacheClear(): void {
    $this->config('system.site')->set('page.front', '/node/1')->save();
    // Not sure why this cache is affecting the front page?
    $this->container->get('cache.data')->deleteAll();

    $this->drupalGet('<front>');
    $this->assertSession()->titleEquals('Test homepage | Drupal');
  }

  /**
   * Tests setting the front page via admin, and getting front page.
   * 
   * This test will succeed, but not sure why.
   */
  public function testFrontpageSetViaAdmin(): void {
    $this->drupalLogin($this->drupalCreateUser(['access content','administer site configuration',]));
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm(['site_frontpage' => '/node/1'], 'Save configuration');
    $this->drupalLogout();

    $this->drupalGet('<front>');
    $this->assertSession()->titleEquals('Test homepage | Drupal');
  }

}
