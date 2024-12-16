<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional\migrate\d7;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;

/**
 * Tests migration that has a dependency on I18nQueryTrait.
 *
 * @group block_content
 */
class I18nQueryTraitTest extends MigrateUpgradeExecuteTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'custom_block_translation_test',
    'migrate_drupal_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture($this->getModulePath('migrate_drupal') . '/tests/fixtures/drupal7.php');
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath(): null {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths(): array {
    return [];
  }

  /**
   * Tests that I18nQueryTrait is available for migrations.
   */
  public function testUpgradeStart(): void {
    // Ensure the test error log is empty before migrate start.
    $this->assertFileDoesNotExist($this->root . '/' . $this->siteDirectory . '/error.log', 'Error.log not empty before start.');

    // Start the upgrade process.
    $this->submitCredentialForm();

    // No fatal error after form submit.
    $this->assertFileDoesNotExist($this->root . '/' . $this->siteDirectory . '/error.log', 'Fatal error on migrate start.');
    $this->assertSession()->pageTextContains('Upgrade analysis report');
  }

}
