<?php

namespace Drupal\Core\Cache;

/**
 * Provides an interface for cache clearers.
 */
interface CacheClearerInterface {

  /**
   * Clears the cache.
   */
  public function clearCache(): void;

}
