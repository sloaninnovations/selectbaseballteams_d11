<?php

namespace Drupal\Core\Cache;

/**
 * A cache clearer for cache bins.
 */
class CacheBinsClearer implements CacheClearerInterface {

  /**
   * {@inheritdoc}
   */
  public function clearCache(): void {
    foreach (Cache::getBins() as $cache_backend) {
      $cache_backend->deleteAll();
    }
  }

}
