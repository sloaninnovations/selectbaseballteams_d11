<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional\d7;

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
    // Start the upgrade process.
    $this->submitCredentialForm();
    $this->assertSession()->pageTextNotContains('Fatal error: Trait "Drupal\content_translation\Plugin\migrate\source\I18nQueryTrait" not found');
    $this->assertSession()->pageTextContains('Upgrade analysis report');
  }

}
