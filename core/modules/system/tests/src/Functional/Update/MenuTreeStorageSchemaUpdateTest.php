<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update of menu tree storage fields.
 *
 * @group system
 */
class MenuTreeStorageSchemaUpdateTest extends UpdatePathTestBase {

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    /** @var \Drupal\Core\Database\Connection $connection */
    $this->connection = \Drupal::service('database');
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      // Start with a standard install of Drupal 10.3.0.
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests DB behavior in initial state.
   */
  public function testSchemaLength(): void {
    if (\Drupal::service('database')->databaseType() == 'sqlite') {
      $this->markTestSkipped("This test does not support the SQLite database driver.");
    }

    $this->expectException(DatabaseExceptionWrapper::class);
    $this->connection->update('menu_tree')
      ->fields([
        'url' => $this->randomMachineName(300),
        'route_param_key' => $this->randomMachineName(300),
      ])
      ->condition('mlid', 1)
      ->execute();
  }

  /**
   * Tests DB behavior after update.
   */
  public function testSchemaLengthAfterUpdate(): void {
    if (\Drupal::service('database')->databaseType() == 'sqlite') {
      $this->markTestSkipped("This test does not support the SQLite database driver.");
    }

    $this->runUpdates();
    $this->connection->update('menu_tree')
      ->fields([
        'url' => $this->randomMachineName(300),
        'route_param_key' => $this->randomMachineName(300),
      ])
      ->condition('mlid', 1)
      ->execute();
  }

}
