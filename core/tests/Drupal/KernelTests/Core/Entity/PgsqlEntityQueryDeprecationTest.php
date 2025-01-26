<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Query\Sql\pgsql\Condition;
use Drupal\Core\Entity\Query\Sql\pgsql\QueryFactory;

/**
 * Tests the deprecation of the PostgreSQL override of the EntityQuery.
 *
 * @group Entity
 * @group legacy
 */
class PgsqlEntityQueryDeprecationTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * Test the deprecation of the PostgreSQL override of the EntityQuery.
   */
  public function testPgsqlOverrideEntityQuery(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test');
    $query = $storage->getQuery();
    $connection = $this->container->get('database');

    $this->expectDeprecation('\Drupal\Core\Entity\Query\Sql\pgsql\Condition is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. The PostgreSQL override of the entity query has been moved to the pgsql module. See https://www.drupal.org/node/3488580');
    $condition = new Condition('AND', $query);
    $this->assertInstanceOf(Condition::class, $condition);

    $this->expectDeprecation('\Drupal\Core\Entity\Query\Sql\pgsql\QueryFactory is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. The PostgreSQL override of the entity query has been moved to the pgsql module. See https://www.drupal.org/node/3488580');
    $factory = new QueryFactory($connection);
    $this->assertInstanceOf(QueryFactory::class, $factory);
  }

}
