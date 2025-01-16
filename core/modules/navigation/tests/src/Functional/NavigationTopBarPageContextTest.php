<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\user\UserInterface;

/**
 * Tests the PageContext top bar item functionality.
 *
 * @group navigation
 */
class NavigationTopBarPageContextTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'navigation',
    'navigation_top_bar',
    'test_page_test',
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
  protected UserInterface $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
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
  public function testPageContextTopBarItemNode(): void {
    // Create a published node entity.
    $node = $this->createNode([
      'type' => 'article',
      'title' => 'No easy twist on the bow',
      'status' => 1,
      'uid' => $this->adminUser->id(),
    ]);

    $test_page_url = Url::fromRoute('test_page_test.test_page');
    $this->drupalGet($test_page_url);
    // Ensure the top bar item is not present.
    $this->assertSession()->elementNotExists('css', '.navigation-top-bar-context');

    // Test the PageContext output for the published node.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    // Ensure the top bar exists
    $this->assertSession()->elementExists('css', '.navigation-top-bar-context');
    // Check the node title
    $this->assertSession()->pageTextContains('No easy twist on the bow');
    // Check the CSS class for published status
    $this->assertSession()->elementContains('css', '.context-status.published', 'Published');

    // Unpublish the node.
    $node->setUnpublished();
    $node->save();

    // Test the PageContext output for the unpublished node.
    $this->drupalGet($node->toUrl());
    // Check the node title
    $this->assertSession()->pageTextContains('No easy twist on the bow');
    // Check the CSS class for unpublished status
    $this->assertSession()->elementContains('css', '.context-status.unpublished', 'Unpublished');
  }

}
