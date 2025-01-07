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
    $memory_cache = $this->getLruMemoryCache(3);

    $cids = [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ];
    foreach ($cids as $items) {
      $memory_cache->set($items[0], $items[1]);
    }
    $this->assertCids($memory_cache, [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ]);

    $memory_cache->set('cuckoo', 'cuckoo');
    $this->assertCids($memory_cache, [
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
      ['cuckoo', 'cuckoo'],
    ]);

    // Now bring pidgin to the most recently used spot.
    $memory_cache->get('pidgin');
    $memory_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->assertCids($memory_cache, [
      ['cuckoo', 'cuckoo'],
      ['pidgin', 'pidgin'],
      ['bigger_cuckoo', 'bigger_cuckoo'],
    ]);

    // Confirm that setting the same item multiple times only uses one slot.
    $memory_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $memory_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $memory_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $memory_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $memory_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->assertCids($memory_cache, [
      ['cuckoo', 'cuckoo'],
      ['pidgin', 'pidgin'],
      ['bigger_cuckoo', 'bigger_cuckoo'],
    ]);

    // Confirm that deleting the same item multiple times only frees up one
    // slot.
    $memory_cache->delete('bigger_cuckoo');
    $memory_cache->delete('bigger_cuckoo');
    $memory_cache->delete('bigger_cuckoo');
    $memory_cache->delete('bigger_cuckoo');
    $memory_cache->delete('bigger_cuckoo');
    $memory_cache->delete('bigger_cuckoo');
    $memory_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->assertCids($memory_cache, [
      ['cuckoo', 'cuckoo'],
      ['pidgin', 'pidgin'],
      ['bigger_cuckoo', 'bigger_cuckoo'],
    ]);
    $memory_cache->set('crow', 'crow');

    $this->assertCids($memory_cache, [
      ['pidgin', 'pidgin'],
      ['bigger_cuckoo', 'bigger_cuckoo'],
      ['crow', 'crow'],
    ]);

    $cids = ['crow', 'pidgin'];
    $memory_cache->getMultiple($cids);
    // @todo This result suggests the order of the arguments in the
    //   \Drupal\Core\Cache\MemoryBackend::getMultiple() array_intersect_key()
    //   should be swapped as this order of the cache items returned should
    //   probably be in the same order as the passed in $cids. I.e. pidgin
    //   should be at the ends of the array and not crow.
    $this->assertCids($memory_cache, [
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
    $memory_cache = $this->getLruMemoryCache(3);

    $cids = [
      [4, 'sparrow'],
      [10, 'pidgin'],
      [7, 'crow'],
    ];
    foreach ($cids as $item) {
      $memory_cache->set($item[0], $item[1]);
    }
    $this->assertCids($memory_cache, $cids);

    $memory_cache->set(1, 'cuckoo');
    $this->assertCids($memory_cache, [
      [10, 'pidgin'],
      [7, 'crow'],
      [1, 'cuckoo'],
    ]);

    $memory_cache->set(7, 'crow');
    $this->assertCids($memory_cache, [
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
    $memory_cache = $this->getLruMemoryCache(3);

    $memory_cache->setMultiple([
      'sparrow' => ['data' => 'sparrow'],
      'pidgin' => ['data' => 'pidgin'],
      'crow' => ['data' => 'crow'],
    ]);
    $this->assertCids($memory_cache, [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ]);

    $memory_cache->setMultiple([
      'sparrow' => ['data' => 'sparrow2'],
      'bluejay' => ['data' => 'bluejay'],
    ]);
    $this->assertCids($memory_cache, [
      ['crow', 'crow'],
      ['sparrow', 'sparrow2'],
      ['bluejay', 'bluejay'],
    ]);

    $memory_cache->setMultiple([
      3 => ['data' => 'pidgin'],
      2 => ['data' => 'eagle'],
      1 => ['data' => 'wren'],
    ]);
    $this->assertCids($memory_cache, [
      [3, 'pidgin'],
      [2, 'eagle'],
      [1, 'wren'],
    ]);

    $memory_cache->setMultiple([
      2 => ['data' => 'eagle2'],
      4 => ['data' => 'cuckoo'],
    ]);
    $this->assertCids($memory_cache, [
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
    $memory_cache = $this->getLruMemoryCache(3);

    $cids = [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ];
    foreach ($cids as $items) {
      $memory_cache->set($items[0], $items[1]);
    }
    $this->assertCids($memory_cache, [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ]);
    $memory_cache->invalidate('crow');
    $this->assertCids($memory_cache, [
      ['crow', 'crow'],
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
    ]);
    $this->assertFalse($memory_cache->get('crow'));
    // Ensure that getting an invalid cache does not move it to the end of the
    // array.
    $this->assertSame('crow', $memory_cache->get('crow', TRUE)->data);
    $this->assertCids($memory_cache, [
      ['crow', 'crow'],
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
    ]);
    $memory_cache->set('cuckoo', 'cuckoo', LruMemoryCache::CACHE_PERMANENT, ['cuckoo']);
    $this->assertCids($memory_cache, [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['cuckoo', 'cuckoo'],
    ]);
    $memory_cache->invalidateTags(['cuckoo']);
    $this->assertFalse($memory_cache->get('cuckoo'));
    $this->assertSame('cuckoo', $memory_cache->get('cuckoo', TRUE)->data);
    $memory_cache->set('crow', 'crow');
    $this->assertCids($memory_cache, [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ]);

    $memory_cache->invalidateMultiple(['pidgin', 'crow']);
    $cids = ['pidgin', 'crow'];
    $this->assertEmpty($memory_cache->getMultiple($cids));
    $this->assertSame(['pidgin', 'crow'], $cids);
    $this->assertCount(2, $memory_cache->getMultiple($cids, TRUE));
    $this->assertSame([], $cids);
    $this->assertCids($memory_cache, [
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
      ['sparrow', 'sparrow'],
    ]);
    $memory_cache->set('duck', 'duck');
    $memory_cache->set('chicken', 'chicken');
    $this->assertCids($memory_cache, [
      ['sparrow', 'sparrow'],
      ['duck', 'duck'],
      ['chicken', 'chicken'],
    ]);
  }

  /**
   * Assert that the given cache ID's match the given value in the memory cache.
   *
   * @param \Drupal\Core\Cache\MemoryCache\LruMemoryCache $memory_cache
   *   The LRU cache under test.
   * @param array $cids
   *   Array whose first element is the cache ID and whose second element is
   *   the value to check. This should contain all the keys in the cache and in
   *   the expected order.
   */
  protected function assertCids(LruMemoryCache $memory_cache, array $cids): void {
    // Use reflection to access data because using ::get() affects the LRU
    // cache.
    $reflectedClass = new \ReflectionClass($memory_cache);
    $reflection = $reflectedClass->getProperty('cache');
    $cache = $reflection->getValue($memory_cache);

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
