<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Kernel\mysql;

use Drupal\mysql\Driver\Database\mysql\Connection;
use Drupal\KernelTests\Core\Database\DriverSpecificKernelTestBase;
use Drupal\Tests\Core\Database\Stub\StubPDO;

/**
 * Tests the deprecations of the MySQL database driver.
 *
 * @group Database
 * @group legacy
 */
class MysqlDriverTest extends DriverSpecificKernelTestBase {

  /**
   * @covers \Drupal\mysql\Driver\Database\mysql\Connection
   */
  public function testConnection(): void {
    $this->expectDeprecation("Not passing the \$eventDispatcher parameter to Drupal\\mysql\\Driver\\Database\\mysql\\Connection::__construct() is deprecated in drupal:11.2.0 and is throwing an error from drupal:12.0.0. See https://www.drupal.org/node/3494044");
    $connection = new Connection($this->createMock(StubPDO::class), []);
    $this->assertInstanceOf(Connection::class, $connection);
  }

}
