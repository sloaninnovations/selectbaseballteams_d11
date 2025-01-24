<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Kernel\pgsql;

use Drupal\pgsql\Driver\Database\pgsql\Connection;
use Drupal\KernelTests\Core\Database\DriverSpecificKernelTestBase;
use Drupal\Tests\Core\Database\Stub\StubPDO;

/**
 * Tests the deprecations of the PostgreSql database driver.
 *
 * @group Database
 * @group legacy
 */
class PgsqlDriverTest extends DriverSpecificKernelTestBase {

  /**
   * @covers \Drupal\pgsql\Driver\Database\pgsql\Connection
   */
  public function testConnection(): void {
    $this->expectDeprecation("Not passing the \$eventDispatcher parameter to Drupal\\pgsql\\Driver\\Database\\pgsql\\Connection::__construct() is deprecated in drupal:11.2.0 and is throwing an error from drupal:12.0.0. See https://www.drupal.org/node/3494044");
    $connection = new Connection($this->createMock(StubPDO::class), []);
    $this->assertInstanceOf(Connection::class, $connection);
  }

}
