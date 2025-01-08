<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\MemoryCache\LruMemoryCache;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\MemoryCache\LruMemoryCache
 * @group Cache
 */
class LruMemoryCacheTest extends UnitTestCase {

  /**
   * Tests getting, setting and deleting items from the LRU memory cache.
   *
   * @covers ::get
   * @covers ::set
   * @covers ::delete
   * @covers ::getMultiple
   */
  public function testGetSetDelete(): void {
    $lru_cache = $this->getLruMemoryCache(3);

    $cids = [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ];
    foreach ($cids as $items) {
      $lru_cache->set($items[0], $items[1]);
    }
    $this->assertCids($lru_cache, [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ]);

    $lru_cache->set('cuckoo', 'cuckoo');
    $this->assertCids($lru_cache, [
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
      ['cuckoo', 'cuckoo'],
    ]);

    // Now bring pidgin to the most recently used spot.
    $lru_cache->get('pidgin');
    $lru_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->assertCids($lru_cache, [
      ['cuckoo', 'cuckoo'],
      ['pidgin', 'pidgin'],
      ['bigger_cuckoo', 'bigger_cuckoo'],
    ]);

    // Confirm that setting the same item multiple times only uses one slot.
    $lru_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $lru_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $lru_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $lru_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $lru_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->assertCids($lru_cache, [
      ['cuckoo', 'cuckoo'],
      ['pidgin', 'pidgin'],
      ['bigger_cuckoo', 'bigger_cuckoo'],
    ]);

    // Confirm that deleting the same item multiple times only frees up one
    // slot.
    $lru_cache->delete('bigger_cuckoo');
    $lru_cache->delete('bigger_cuckoo');
    $lru_cache->delete('bigger_cuckoo');
    $lru_cache->delete('bigger_cuckoo');
    $lru_cache->delete('bigger_cuckoo');
    $lru_cache->delete('bigger_cuckoo');
    $lru_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->assertCids($lru_cache, [
      ['cuckoo', 'cuckoo'],
      ['pidgin', 'pidgin'],
      ['bigger_cuckoo', 'bigger_cuckoo'],
    ]);
    $lru_cache->set('crow', 'crow');

    $this->assertCids($lru_cache, [
      ['pidgin', 'pidgin'],
      ['bigger_cuckoo', 'bigger_cuckoo'],
      ['crow', 'crow'],
    ]);

    $cids = ['crow', 'pidgin'];
    $lru_cache->getMultiple($cids);
    // @todo This result suggests the order of the arguments in the
    //   \Drupal\Core\Cache\MemoryBackend::getMultiple() array_intersect_key()
    //   should be swapped as this order of the cache items returned should
    //   probably be in the same order as the passed in $cids. I.e. pidgin
    //   should be at the ends of the array and not crow.
    $this->assertCids($lru_cache, [
      ['bigger_cuckoo', 'bigger_cuckoo'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ]);
  }

  /**
   * Tests setting items with numeric keys in the LRU memory cache.
   *
   * @covers ::set
   */
  public function testSetNumericKeys(): void {
    $lru_cache = $this->getLruMemoryCache(3);

    $cids = [
      [4, 'sparrow'],
      [10, 'pidgin'],
      [7, 'crow'],
    ];
    foreach ($cids as $item) {
      $lru_cache->set($item[0], $item[1]);
    }
    $this->assertCids($lru_cache, $cids);

    $lru_cache->set(1, 'cuckoo');
    $this->assertCids($lru_cache, [
      [10, 'pidgin'],
      [7, 'crow'],
      [1, 'cuckoo'],
    ]);

    $lru_cache->set(7, 'crow');
    $this->assertCids($lru_cache, [
      [10, 'pidgin'],
      [1, 'cuckoo'],
      [7, 'crow'],
    ]);
  }

  /**
   * Tests setting multiple items in the LRU memory cache.
   *
   * @covers ::setMultiple
   */
  public function testSetMultiple(): void {
    $lru_cache = $this->getLruMemoryCache(3);

    $lru_cache->setMultiple([
      'sparrow' => ['data' => 'sparrow'],
      'pidgin' => ['data' => 'pidgin'],
      'crow' => ['data' => 'crow'],
    ]);
    $this->assertCids($lru_cache, [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ]);

    $lru_cache->setMultiple([
      'sparrow' => ['data' => 'sparrow2'],
      'bluejay' => ['data' => 'bluejay'],
    ]);
    $this->assertCids($lru_cache, [
      ['crow', 'crow'],
      ['sparrow', 'sparrow2'],
      ['bluejay', 'bluejay'],
    ]);

    $lru_cache->setMultiple([
      3 => ['data' => 'pidgin'],
      2 => ['data' => 'eagle'],
      1 => ['data' => 'wren'],
    ]);
    $this->assertCids($lru_cache, [
      [3, 'pidgin'],
      [2, 'eagle'],
      [1, 'wren'],
    ]);

    $lru_cache->setMultiple([
      2 => ['data' => 'eagle2'],
      4 => ['data' => 'cuckoo'],
    ]);
    $this->assertCids($lru_cache, [
      [1, 'wren'],
      [2, 'eagle2'],
      [4, 'cuckoo'],
    ]);
  }

  /**
   * Tests invalidation from the LRU memory cache.
   *
   * @covers ::invalidate
   * @covers ::invalidateMultiple
   */
  public function testInvalidate(): void {
    $lru_cache = $this->getLruMemoryCache(3);

    $cids = [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ];
    foreach ($cids as $items) {
      $lru_cache->set($items[0], $items[1]);
    }
    $this->assertCids($lru_cache, [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ]);
    $lru_cache->invalidate('crow');
    $this->assertCids($lru_cache, [
      ['crow', 'crow'],
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
    ]);
    $this->assertFalse($lru_cache->get('crow'));
    // Ensure that getting an invalid cache does not move it to the end of the
    // array.
    $this->assertSame('crow', $lru_cache->get('crow', TRUE)->data);
    $this->assertCids($lru_cache, [
      ['crow', 'crow'],
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
    ]);
    $lru_cache->set('cuckoo', 'cuckoo', LruMemoryCache::CACHE_PERMANENT, ['cuckoo']);
    $this->assertCids($lru_cache, [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['cuckoo', 'cuckoo'],
    ]);
    $lru_cache->invalidateTags(['cuckoo']);
    $this->assertFalse($lru_cache->get('cuckoo'));
    $this->assertSame('cuckoo', $lru_cache->get('cuckoo', TRUE)->data);
    $lru_cache->set('crow', 'crow');
    $this->assertCids($lru_cache, [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ]);

    $lru_cache->invalidateMultiple(['pidgin', 'crow']);
    $cids = ['pidgin', 'crow'];
    $this->assertEmpty($lru_cache->getMultiple($cids));
    $this->assertSame(['pidgin', 'crow'], $cids);
    $this->assertCount(2, $lru_cache->getMultiple($cids, TRUE));
    $this->assertSame([], $cids);
    $this->assertCids($lru_cache, [
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
      ['sparrow', 'sparrow'],
    ]);
    $lru_cache->set('duck', 'duck');
    $lru_cache->set('chicken', 'chicken');
    $this->assertCids($lru_cache, [
      ['sparrow', 'sparrow'],
      ['duck', 'duck'],
      ['chicken', 'chicken'],
    ]);
  }

  /**
   * Tests invalidation with numeric keys from the LRU memory cache.
   *
   * @covers ::invalidate
   * @covers ::invalidateMultiple
   */
  public function testInvalidateNumeric(): void {
    $lru_cache = $this->getLruMemoryCache(3);

    $cids = [
      [3, 'sparrow'],
      [10, 'pidgin'],
      [5, 'crow'],
    ];
    foreach ($cids as $items) {
      $lru_cache->set($items[0], $items[1]);
    }
    $this->assertCids($lru_cache, [
      [3, 'sparrow'],
      [10, 'pidgin'],
      [5, 'crow'],
    ]);
    $lru_cache->invalidate(10);
    $this->assertCids($lru_cache, [
      [10, 'pidgin'],
      [3, 'sparrow'],
      [5, 'crow'],
    ]);
    $this->assertFalse($lru_cache->get(10));
    $this->assertSame('pidgin', $lru_cache->get(10, TRUE)->data);
  }

  /**
   * Assert that the given cache ID's match the given value in the memory cache.
   *
   * @param \Drupal\Core\Cache\MemoryCache\LruMemoryCache $lru_cache
   *   The LRU cache under test.
   * @param array $cids
   *   Array whose first element is the cache ID and whose second element is
   *   the value to check. This should contain all the keys in the cache and in
   *   the expected order.
   */
  protected function assertCids(LruMemoryCache $lru_cache, array $cids): void {
    // Use reflection to access data because using ::get() affects the LRU
    // cache.
    $reflectedClass = new \ReflectionClass($lru_cache);
    $reflection = $reflectedClass->getProperty('cache');
    $cache = $reflection->getValue($lru_cache);

    $keys = [];
    foreach ($cids as $item) {
      $keys[] = $item[0];
      $this->assertSame($item[1], $cache[$item[0]]->data, "$item[0] found in cache.");
    }

    // Ensure the cache only contains the supply keys and the order is as
    // expected.
    $this->assertSame($keys, array_keys($cache));
  }

  /**
   * Creates a LRU cache for testing.
   *
   * @param int $slots
   *   The number of slots in the LRU cache.
   *
   * @return \Drupal\Core\Cache\MemoryCache\LruMemoryCache
   *   The LRU cache.
   */
  private function getLruMemoryCache(int $slots): LruMemoryCache {
    $time_mock = $this->createMock(TimeInterface::class);
    $time_mock->expects($this->any())
      ->method('getRequestTime')
      ->willReturnCallback('time');
    return new LruMemoryCache(
      $time_mock,
      $slots,
    );
  }

}
