<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests installing the Testing profile with update notifications on.
 *
 * @group Installer
 */
class TestingProfileRequirements extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'profile_install_requirements';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test hooks are picked up.
   */
  public function testHookPickup(): void {
    $this->assertTrue(isset($GLOBALS['profile_install_requirements']));
  }

}
