<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests installing a profile without version.
 *
 * @group Installer
 */
class InstallerMissingVersionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_install_profile_missing_version';

  /**
   * Tests the report status page after install a module without version.
   */
  public function testProfileOnStatusPage(): void {
    $user = $this->createUser(['administer site configuration', 'access site reports']);
    $this->drupalLogin($user);
    $this->drupalGet('admin/reports/status');

    // Verify that the PHP version is shown on the page.
    $this->assertSession()->pageTextContains('Testing install missing version (testing_install_profile_missing_version)');

  }

}
