<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the PageContext top bar item functionality.
 *
 * @group navigation
 */
class PageContextTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'navigation',
    'navigation_top_bar',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An admin user to configure the test environment.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'access navigation',
      'bypass node access',
    ]);
    $this->drupalLogin($this->adminUser);

    // Ensure the 'article' content type exists.
    $this->createContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Tests the PageContext top bar item output for a published node.
   */
  public function testPageContextPublishedNode(): void {
    // Create a published node entity.
    $published_node = $this->createNode([
      'type' => 'article',
      'title' => 'Published Node',
      'status' => 1,
      'uid' => $this->adminUser->id(),
    ]);

    // Test the PageContext output for the published node.
    $this->drupalGet($published_node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    // Ensure the top bar exists
    $this->assertSession()->elementExists('css', '.navigation-top-bar-context');
    // Check the node title
    $this->assertSession()->pageTextContains('Published Node');
    // Check the published status
    $this->assertSession()->pageTextContains('Published');
    // Check the CSS class for published status
    $this->assertSession()->elementContains('css', '.context-status.published', 'Published');
  }

  /**
   * Tests the PageContext top bar item output for an unpublished node.
   */
  public function testPageContextUnpublishedNode(): void {
    // Create an unpublished node entity.
    $unpublished_node = $this->createNode([
      'type' => 'article',
      'title' => 'Unpublished Node',
      'status' => 0,
      'uid' => $this->adminUser->id(),
    ]);

    // Test the PageContext output for the unpublished node.
    $this->drupalGet($unpublished_node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    // Ensure the top bar exists
    $this->assertSession()->elementExists('css', '.navigation-top-bar-context');
    // Check the node title
    $this->assertSession()->pageTextContains('Unpublished Node');
    // Check the unpublished status
    $this->assertSession()->pageTextContains('Unpublished');
    // Check the CSS class for unpublished status
    $this->assertSession()->elementContains('css', '.context-status.unpublished', 'Unpublished');
  }

}
