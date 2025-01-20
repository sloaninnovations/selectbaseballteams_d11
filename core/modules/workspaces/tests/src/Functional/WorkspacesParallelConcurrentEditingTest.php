<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces_parallel\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workspaces\Functional\WorkspaceTestUtilities;

/**
 * Tests concurrent edits in different workspaces.
 *
 * @group workspaces
 */
class WorkspacesParallelConcurrentEditingTest extends BrowserTestBase {

  use WorkspaceTestUtilities;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'node', 'workspaces'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests editing a node in multiple workspaces.
   */
  public function testParallelEditing(): void {
    $this->config('workspaces.settings')->set('parallel', TRUE)->save();

    // Create a test node.
    $this->createContentType(['type' => 'test', 'label' => 'Test']);
    $this->setupWorkspaceSwitcherBlock();

    $permissions = [
      'create workspace',
      'edit own workspace',
      'view own workspace',
      'create test content',
      'edit own test content',
    ];
    $mayer = $this->drupalCreateUser($permissions);
    $this->drupalLogin($mayer);

    $test_node = $this->createNodeThroughUi('Test node', 'test');

    // Check that the user can edit the node.
    $page = $this->getSession()->getPage();
    $page->hasField('title[0][value]');

    // Create two workspaces.
    $vultures = $this->createWorkspaceThroughUi('Vultures', 'vultures');
    $gravity = $this->createWorkspaceThroughUi('Gravity', 'gravity');

    // Edit the node in workspace 'vultures'.
    $this->switchToWorkspace($vultures);
    $this->drupalGet('/node/' . $test_node->id() . '/edit');
    $page = $this->getSession()->getPage();
    $page->fillField('Title', 'Test node - override');
    $page->findButton('Save')->click();

    // Check that the user can still edit the node in the same workspace.
    $this->drupalGet('/node/' . $test_node->id() . '/edit');
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasField('title[0][value]'));

    // Switch to a different workspace and check that the user can still edit
    // the node.
    $this->switchToWorkspace($gravity);
    $this->drupalGet('/node/' . $test_node->id() . '/edit');
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasField('title[0][value]'));

    // Check that the node passes validation for API calls.
    $violations = $test_node->validate();
    $this->assertCount(0, $violations);

    // Switch to the Live version of the site and check that the user still can
    // edit the node.
    $this->switchToLive();
    $this->drupalGet('/node/' . $test_node->id() . '/edit');
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasField('title[0][value]'));

    // Check that the node passes validation for API calls.
    $violations = $test_node->validate();
    $this->assertCount(0, $violations);
  }

}
