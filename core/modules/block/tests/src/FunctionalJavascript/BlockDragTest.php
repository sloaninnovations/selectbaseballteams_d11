<?php

declare(strict_types=1);

namespace Drupal\Tests\block\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests drag and drop blocks on block layout page.
 *
 * @group block
 */
class BlockDragTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'block', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'administer blocks',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header', 'id' => 'site_branding']);
    $this->drupalPlaceBlock('system_menu_block:main', ['region' => 'primary_menu', 'id' => 'main_menu']);
    $this->drupalPlaceBlock('system_powered_by_block', ['region' => 'footer', 'id' => 'powered_by']);
    $this->drupalPlaceBlock('system_breadcrumb_block', ['region' => 'breadcrumb', 'id' => 'breadcrumb']);
  }

  /**
   * Tests drag and drop blocks.
   */
  public function testDragAndDropBlocks() {
    $this->drupalGet('admin/structure/block');
    $assertSession = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    // Drag main-menu and powered-by blocks to header region.
    $siteBranding = $this->getDragRow($page, 'edit-blocks-site-branding');
    $mainMenuRow = $this->getDragRow($page, 'edit-blocks-main-menu');
    $mainMenuRow->dragTo($siteBranding);
    $poweredBy = $this->getDragRow($page, 'edit-blocks-powered-by');
    $poweredBy->dragTo($siteBranding);

    // Test if both blocks were positioned in the header region.
    $this->assertEquals(
      'header',
      $page->findField('edit-blocks-main-menu-region')->getValue()
    );
    $this->assertEquals(
      'header',
      $page->findField('edit-blocks-powered-by-region')->getValue()
    );

    // Check if the message about unsaved changed appears.
    $assertSession->pageTextContains('You have unsaved changes.');

    // Test if the message for empty regions appears on the primary-menu region,
    // which should now be empty after dragging the only block out of there.
    $noBlockMessage = $page->find('css', 'tr[data-drupal-selector="edit-blocks-region-primary-menu-message"] td')->getText();

    $this->assertEquals('No blocks in this region', $noBlockMessage);

    // Test if dragging a row to an empty region removes empty region message.
    $breadcrumbs = $this->getDragRow($page, 'edit-blocks-breadcrumb');
    $heroRegion = $page->find('css', 'tr[data-drupal-selector="edit-blocks-region-highlighted-message"]');
    $breadcrumbs->dragTo($heroRegion);
    $this->assertNotEquals('No blocks in this region', $page->find('css', 'tr[data-drupal-selector="edit-blocks-region-highlighted-message"] td')->getText());
  }

  /**
   * Helper function to find the tabledrag handle on a table-row element.
   */
  private function getDragRow($page, $blockId) {
    return $page->find('css', '#blocks tbody tr[data-drupal-selector="' . $blockId . '"] a.tabledrag-handle');
  }

}
