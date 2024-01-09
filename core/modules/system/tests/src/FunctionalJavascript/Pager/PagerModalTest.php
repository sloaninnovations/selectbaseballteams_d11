<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript\Pager;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests pager functionality.
 *
 * @group Pager
 */
class PagerModalTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dblog', 'pager_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to access site reports.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Insert 300 log messages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 300; $i++) {
      $logger->debug($this->randomString());
    }
  }

  /**
   * Tests pagers work inside of modals.
   */
  public function testPagerInsideModal(): void {
    $this->drupalGet(Url::fromRoute('pager_test.modal_pager'));

    $this->clickLink('Open modal');
    $this->assertSession()->waitForElementVisible('css', '.pager-test-modal');

    $this->assertSession()->responseContains('Pagers in modal');
    $this->assertSession()->elementExists('css', '.test-pager-0')->clickLink('Go to page 2');

    $this->assertTrue($this->assertSession()->waitForText('Current page 2'));
  }

}
