<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Kernel\mysql;

use Drupal\KernelTests\Core\Database\DriverSpecificDatabaseTestBase;

/**
 * Tests compatibility of the MySQL driver when disabling the ANSI_QUOTES sql_mode option.
 *
 * Also contains a control test that confirms the behavior of the database query parser
 * when ONLY_FULL_GROUP_BY is not enabled.
 *
 * @group Database
 */
class SqlModeTest extends DriverSpecificDatabaseTestBase {

  /**
   * Tests quoting identifiers in queries.
   */
  public function testQuotingIdentifiers(): void {
    // Use SQL-reserved words for both the table and column names.
    $query = $this->connection->query('SELECT [update] FROM {select}');
    $this->assertEquals('Update value 1', $query->fetchObject()->update);
    $this->assertStringContainsString('SELECT `update` FROM `', $query->getQueryString());
  }

  /**
   * Tests behavior when ONLY_FULL_GROUP_BY is not set.
   */
  public function testOnlyFullGroupByDisabled(): void {
    // No SQL modes are set, so ONLY_FULL_GROUP_BY is therefore not set, so this query should succeed.
    // Note that this is the same query that fails in testOnlyFullGroupByEnabled, the only difference
    // being that in the later test, ONLY_FULL_GROUP_BY is set.
    $query = $this->connection->query('SELECT name, job FROM {test} GROUP BY job');
    $this->assertEquals('Singer', $query->fetchObject()->job);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDatabaseConnectionInfo() {
    $info = parent::getDatabaseConnectionInfo();

    // This runs during setUp(), so is not yet skipped for non MySQL databases.
    // We defer skipping the test to later in setUp(), so that that can be
    // based on databaseType() rather than 'driver', but here all we have to go
    // on is 'driver'.
    if ($info['default']['driver'] === 'mysql') {
      $info['default']['init_commands']['sql_mode'] = "SET sql_mode = ''";
    }

    return $info;
  }

}
