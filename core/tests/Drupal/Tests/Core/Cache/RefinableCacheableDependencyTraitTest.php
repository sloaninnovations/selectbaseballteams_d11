<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the cache RefinableCacheableDependencyTrait.
 *
 * @group Cache
 */
class RefinableCacheableDependencyTraitTest extends UnitTestCase {

  use RefinableCacheableDependencyTrait;

  /**
   * @group legacy
   */
  public function testNonCacheableDependencyAddDeprecation(): void {
    $this->expectDeprecation("Calling Drupal\Core\Cache\RefinableCacheableDependencyTrait::addCacheableDependency() with an object that doesn't implement Drupal\Core\Cache\CacheableDependencyInterface is deprecated in drupal:10.3.0 and is required in drupal:12.0.0. See https://www.drupal.org/node/3232020");
    $this->addCacheableDependency(new \stdClass());
  }

}
