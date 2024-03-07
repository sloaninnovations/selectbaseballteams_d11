<?php

namespace Drupal\KernelTests\Core\Cache;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the ChainCacheClearer.
 *
 * @group Cache
 * @coversDefaultClass \Drupal\Core\Cache\ChainCacheClearer
 */
class ChainCacheClearerTest extends KernelTestBase {

  /**
   * @covers ::clearCache
   */
  public function testCacheClear(): void {
    /** @var \Drupal\Core\Cache\ChainCacheClearer $chainClearer */
    $chainClearer = $this->container->get('cache.chain_cache_clearer');
    $this->assertNotNull($chainClearer);
    $chainClearer->clearCache();
  }

}
