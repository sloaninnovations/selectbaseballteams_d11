<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update of system.site:* empty values to NULL.
 *
 * @group system
 * @covers \system_post_update_convert_empty_system_site_settings_to_null
 */
class SiteSettingsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests update of system.site:* empty values to NULL.
   */
  public function testUpdate(): void {
    // Fixture already has defaults, so replaced with ''.
    $this->config('system.site')->set('uuid', '')->save();
    $this->config('system.site')->set('name', '')->save();
    $this->config('system.site')->set('mail', '')->save();

    $config_before = $this->config('system.site');
    $this->assertSame('', $config_before->get('uuid'));
    $this->assertSame('', $config_before->get('name'));
    $this->assertSame('', $config_before->get('mail'));
    $this->assertSame('', $config_before->get('slogan'));
    $this->assertSame('', $config_before->get('page.403'));
    $this->assertSame('', $config_before->get('page.404'));

    $this->runUpdates();

    $config_after = $this->config('system.site');
    $this->assertNull($config_after->get('uuid'));
    $this->assertNull($config_after->get('name'));
    $this->assertNull($config_after->get('mail'));
    $this->assertNull($config_after->get('slogan'));
    $this->assertNull($config_after->get('page.403'));
    $this->assertNull($config_after->get('page.404'));
  }

}
