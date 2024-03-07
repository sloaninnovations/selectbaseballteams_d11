<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Core\Cache\CacheClearerInterface;

/**
 * Defines a class which is capable of clearing the cache on plugin managers.
 */
class CachedDiscoveryClearer implements CachedDiscoveryClearerInterface, CacheClearerInterface {

  /**
   * The stored discoveries.
   *
   * @var \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface[]
   */
  protected $cachedDiscoveries = [];

  /**
   * {@inheritdoc}
   */
  public function addCachedDiscovery(CachedDiscoveryInterface $cached_discovery) {
    $this->cachedDiscoveries[] = $cached_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    foreach ($this->cachedDiscoveries as $cached_discovery) {
      $cached_discovery->clearCachedDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache(): void {
    $this->clearCachedDefinitions();
  }

}
