<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;

/**
 * Tests the PageContext top bar item functionality.
 *
 * @group navigation
 */
class PageContextTest extends BrowserTestBase {

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

    // Ensure that the 'article' content type exists before using it.
    $node_type = NodeType::load('article');
    if (!$node_type) {
      // Create the article content type if it doesn't exist.
      $node_type = NodeType::create([
        'type' => 'article',
        'name' => 'Article',
      ]);
      $node_type->setDisplaySubmitted(TRUE);
      $node_type->save();
    }
  }

  /**
   * Creates a node with the given status.
   *
   * @param bool $published
   *   Whether the node should be published.
   * @param string $title
   *   The node title.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node.
   */
  private function nodeCreation(bool $published, string $title): Node {
    $node = Node::create([
      'type' => 'article',
      'title' => $title,
      'status' => $published ? 1 : 0,
      'uid' => $this->adminUser->id(),
    ]);
    $node->save();

    return $node;
  }

  /**
   * Tests the PageContext top bar item output for a published node.
   */
  public function testPageContextPublishedNode(): void {
    // Create a published node entity.
    $published_node = $this->nodeCreation(TRUE, 'Published Node');

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
    $unpublished_node = $this->nodeCreation(FALSE, 'Unpublished Node');

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
