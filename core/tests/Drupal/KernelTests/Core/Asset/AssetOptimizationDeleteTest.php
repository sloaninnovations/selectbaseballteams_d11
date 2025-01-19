<?php

namespace Drupal\KernelTests\Core\Asset;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests asset aggregation deletion.
 *
 * @group asset
 */
class AssetOptimizationDeleteTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'dblog'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('dblog', ['watchdog']);
  }

  /**
   * Tests that deleting non existent assets doesn't log an error.
   */
  public function testAssetAggregationDeletion(): void {
    /** @var \Drupal\Core\Asset\CssCollectionOptimizerLazy $cssOptimizer */
    $cssOptimizer = \Drupal::service('asset.css.collection_optimizer');
    /** @var \Drupal\Core\Asset\JsCollectionOptimizerLazy $jsOptimizer */
    $jsOptimizer = \Drupal::service('asset.js.collection_optimizer');

    // Ensure aggregated assets don't exist.
    $this->assertFalse(\file_exists('assets://css'));
    $this->assertFalse(\file_exists('assets://js'));

    // Calling deleteAll when asset directories don't exist shouldn't log
    // anything.
    $logCount = $this->getLogCount();
    $cssOptimizer->deleteAll();
    $jsOptimizer->deleteAll();
    $this->assertEquals($logCount, $this->getLogCount());
  }

  /**
   * Get the number of logs in watchdog.
   *
   * @return int
   *   The number of logs.
   */
  protected function getLogCount(): int {
    return \Drupal::database()
      ->select('watchdog')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
