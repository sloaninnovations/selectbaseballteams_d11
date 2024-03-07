<?php

namespace Drupal\Core\Cache;

/**
 * A cache clearer that calls drupal_static_reset().
 */
class DrupalStaticCacheClearer implements CacheClearerInterface {

  /**
   * {@inheritdoc}
   */
  public function clearCache(): void {
    drupal_static_reset();
  }

}
