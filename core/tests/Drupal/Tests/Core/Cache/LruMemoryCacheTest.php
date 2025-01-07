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
   * The static cache.
   *
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface
   */
  protected $memoryCache;

  /**
   * Tests getting, setting and deleting items from the LRU memory cache.
   *
   * @covers ::get
   * @covers ::set
   * @covers ::delete
   */
  public function testGetSetDelete(): void {
    $this->memoryCache = new LruMemoryCache(
      $this->createMock(TimeInterface::class),
      3,
    );
    $cids = [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ];
    foreach ($cids as $items) {
      $this->memoryCache->set($items[0], $items[1]);
    }
    $this->assertCids([
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ]);

    $this->memoryCache->set('cuckoo', 'cuckoo');
    $this->assertCids([
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
      ['cuckoo', 'cuckoo'],
    ]);

    // Now bring pidgin to the most recently used spot.
    $this->memoryCache->get('pidgin');
    $this->memoryCache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->assertCids([
      ['cuckoo', 'cuckoo'],
      ['pidgin', 'pidgin'],
      ['bigger_cuckoo', 'bigger_cuckoo'],
    ]);

    // Confirm that setting the same item multiple times only uses one slot.
    $this->memoryCache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->memoryCache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->memoryCache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->memoryCache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->memoryCache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->assertCids([
      ['cuckoo', 'cuckoo'],
      ['pidgin', 'pidgin'],
      ['bigger_cuckoo', 'bigger_cuckoo'],
    ]);

    // Confirm that deleting the same item multiple times only frees up one
    // slot.
    $this->memoryCache->delete('bigger_cuckoo');
    $this->memoryCache->delete('bigger_cuckoo');
    $this->memoryCache->delete('bigger_cuckoo');
    $this->memoryCache->delete('bigger_cuckoo');
    $this->memoryCache->delete('bigger_cuckoo');
    $this->memoryCache->delete('bigger_cuckoo');
    $this->memoryCache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->assertCids([
      ['cuckoo', 'cuckoo'],
      ['pidgin', 'pidgin'],
      ['bigger_cuckoo', 'bigger_cuckoo'],
    ]);
    $this->memoryCache->set('crow', 'crow');

    $this->assertCids([
      ['pidgin', 'pidgin'],
      ['bigger_cuckoo', 'bigger_cuckoo'],
      ['crow', 'crow'],
    ]);
  }

  /**
   * Tests setting items with numeric keys in the LRU memory cache.
   *
   * @covers ::set
   */
  public function testSetNumericKeys(): void {
    $this->memoryCache = new LruMemoryCache(
      $this->createMock(TimeInterface::class),
      3,
    );
    $cids = [
      [4, 'sparrow'],
      [10, 'pidgin'],
      [7, 'crow'],
    ];
    foreach ($cids as $item) {
      $this->memoryCache->set($item[0], $item[1]);
    }
    $this->assertCids($cids);

    $this->memoryCache->set(1, 'cuckoo');
    $this->assertCids([
      [10, 'pidgin'],
      [7, 'crow'],
      [1, 'cuckoo'],
    ]);

    $this->memoryCache->set(7, 'crow');
    $this->assertCids([
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
    $this->memoryCache = new LruMemoryCache(
      $this->createMock(TimeInterface::class),
      3,
    );

    $this->memoryCache->setMultiple([
      'sparrow' => ['data' => 'sparrow'],
      'pidgin' => ['data' => 'pidgin'],
      'crow' => ['data' => 'crow'],
    ]);
    $this->assertCids([
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ]);

    $this->memoryCache->setMultiple([
      'sparrow' => ['data' => 'sparrow2'],
      'bluejay' => ['data' => 'bluejay'],
    ]);
    $this->assertCids([
      ['crow', 'crow'],
      ['sparrow', 'sparrow2'],
      ['bluejay', 'bluejay'],
    ]);

    $this->memoryCache->setMultiple([
      3 => ['data' => 'pidgin'],
      2 => ['data' => 'eagle'],
      1 => ['data' => 'wren'],
    ]);
    $this->assertCids([
      [3, 'pidgin'],
      [2, 'eagle'],
      [1, 'wren'],
    ]);

    $this->memoryCache->setMultiple([
      2 => ['data' => 'eagle2'],
      4 => ['data' => 'cuckoo'],
    ]);
    $this->assertCids([
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
    $this->memoryCache = new LruMemoryCache(
      $this->createMock(TimeInterface::class),
      3,
    );
    $cids = [
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ];
    foreach ($cids as $items) {
      $this->memoryCache->set($items[0], $items[1]);
    }
    $this->assertCids([
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ]);
    $this->memoryCache->invalidate('crow');
    $this->memoryCache->set('cuckoo', 'cuckoo', LruMemoryCache::CACHE_PERMANENT, ['cuckoo']);
    $this->assertCids([
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['cuckoo', 'cuckoo'],
    ]);
    $this->memoryCache->invalidateTags(['cuckoo']);
    $this->memoryCache->set('crow', 'crow');
    $this->assertCids([
      ['sparrow', 'sparrow'],
      ['pidgin', 'pidgin'],
      ['crow', 'crow'],
    ]);

    $this->memoryCache->invalidateMultiple(['pidgin', 'crow']);
    $this->memoryCache->set('duck', 'duck');
    $this->memoryCache->set('chicken', 'chicken');
    $this->assertCids([
      ['sparrow', 'sparrow'],
      ['duck', 'duck'],
      ['chicken', 'chicken'],
    ]);
  }

  /**
   * Assert that the given cache ID's match the given value in the memory cache.
   *
   * @param array $cids
   *   Array whose first element is the cache ID and whose second element is
   *   the value to check. This should contain all the keys in the cache and in
   *   the expected order.
   */
  protected function assertCids(array $cids): void {
    // Use reflection to access data because using ::get() affects the LRU
    // cache.
    $reflectedClass = new \ReflectionClass($this->memoryCache);
    $reflection = $reflectedClass->getProperty('cache');
    $cache = $reflection->getValue($this->memoryCache);

    $keys = [];
    foreach ($cids as $item) {
      $keys[] = $item[0];
      $this->assertSame($item[1], $cache[$item[0]]->data, "$item[0] found in cache.");
    }

    // Ensure the cache only contains the supply keys and the order is as
    // expected.
    $this->assertSame($keys, array_keys($cache));
  }

}
