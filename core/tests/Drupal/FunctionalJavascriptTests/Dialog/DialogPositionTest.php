<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Dialog;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JavaScript functionality of the dialog position.
 *
 * @group dialog
 */
class DialogPositionTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests if the dialog UI works properly with block layout page.
   */
  public function testDialogOpenAndClose(): void {
    $admin_user = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/structure/block');
    $session = $this->getSession();
    $assert_session = $this->assertSession();
    $page = $session->getPage();

    // Open the dialog using the place block link.
    $placeBlockLink = $page->findLink('Place block');
    $this->assertTrue($placeBlockLink->isVisible(), 'Place block button exists.');
    $placeBlockLink->click();
    $assert_session->assertWaitOnAjaxRequest();
    $dialog = $page->find('css', '.ui-dialog');
    $this->assertTrue($dialog->isVisible(), 'Dialog is opened after clicking the Place block button.');
    // Ensure the dialog modal got the aria-modal attribute added (introduced in  Drupal 11.0.0)
    // and make sure the dialog title is wrapped in a h1 (introduced in Drupal 11.1.0)
    // so the modal is correctly represented in the aural interface and with the background
    // removed from the AOM the first heading, the title, is a h1 for screen reader users.
    $this->assertEquals('true', $dialog->getAttribute('aria-modal'), 'Dialog modal has aria-modal attribute');
    $dialogTitle = $page->find('css', 'h1.ui-dialog-title');
    $this->assertTrue($dialogTitle->isVisible(), 'Title wrapped in a h1 element');

    // Close the dialog again.
    $closeButton = $page->find('css', '.ui-dialog-titlebar-close');
    $closeButton->click();
    $dialog = $page->find('css', '.ui-dialog');
    $this->assertNull($dialog, 'Dialog is closed after clicking the close button.');

    // Resize the window. The test should pass after waiting for JavaScript to
    // finish as no Javascript errors should have been triggered. If there were
    // javascript errors the test will fail on that.
    $session->resizeWindow(625, 625);
    usleep(5000);
  }

}
