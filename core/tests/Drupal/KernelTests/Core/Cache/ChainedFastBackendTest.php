<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\ObjectAwareSerializationInterface;
use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Cache\ChainedFastBackend;
use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Cache\PhpBackend;
use Drupal\Core\Database\Connection;

/**
 * Unit test of the fast chained backend using the generic cache unit test base.
 *
 * @group Cache
 */
class ChainedFastBackendTest extends GenericCacheBackendUnitTestBase {

  /**
   * Creates a new instance of ChainedFastBackend.
   *
   * @return \Drupal\Core\Cache\ChainedFastBackend
   *   A new ChainedFastBackend object.
   */
  protected function createCacheBackend($bin) {
    $consistent_backend = new DatabaseBackend(
      \Drupal::serviceByClass(Connection::class),
      \Drupal::serviceByClass(CacheTagsChecksumInterface::class),
      $bin,
      \Drupal::serviceByClass(ObjectAwareSerializationInterface::class),
      \Drupal::serviceByClass(TimeInterface::class),
      100,
    );
    $fast_backend = new PhpBackend(
      $bin,
      \Drupal::service('cache_tags.invalidator.checksum'),
      \Drupal::serviceByClass(TimeInterface::class),
    );
    $backend = new ChainedFastBackend($consistent_backend, $fast_backend, $bin);
    // Explicitly register the cache bin as it can not work through the
    // cache bin list in the container.
    \Drupal::serviceByClass(CacheTagsInvalidator::class, CacheTagsInvalidatorInterface::class)->addInvalidator($backend);
    return $backend;
  }

}
