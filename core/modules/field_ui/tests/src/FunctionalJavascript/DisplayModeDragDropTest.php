<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the drag-and-drop functionality in display mode.
 *
 * @group field_ui
 */
class DisplayModeDragDropTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field_ui'];

  /**
   * A user with permissions to manage display modes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->user = $this->drupalCreateUser([
      'administer node display',
      'administer display modes',
      'administer nodes',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Test the drag-and-drop functionality in display mode.
   */
  public function testDragAndDropDisplayMode() {
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->click('#edit-modes');

    $edit = ['display_modes_custom[search_result]' => TRUE];
    $this->submitForm($edit, 'Save');

    $this->drupalGet('admin/structure/types/manage/article/display/search_result');
    $this->assertNotEmpty($this->assertSession()->waitForText("Manage display"));

    $this->getSession()->getPage()->find('css', "#links .tabledrag-handle")->dragTo($this->getSession()->getPage()->find('css', 'tr.region-empty'));
    $this->assertNotEmpty($this->assertSession()->waitForText("You have unsaved changes."));
    $this->getSession()->getPage()->pressButton('Save');
    // Assert that no errors are present and the changes are saved successfully.
    $this->assertNotEmpty($this->assertSession()->waitForText("Your settings have been saved."));
    // Asserts that the empty region is now populated.
    $this->assertSession()->elementNotExists('css', 'tr.region-empty');

  }

}
