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
      $this->memoryCache->set($items[0], $items[0]);
    }
    $this->assertCids($cids);
    $this->memoryCache->set('cuckoo', 'cuckoo');
    $cids[0] = ['sparrow', FALSE];
    $cids[] = ['cuckoo', 'cuckoo'];
    $this->assertCids($cids);

    // Now bring pidgin to the most recently used spot.
    $this->memoryCache->get('pidgin');

    $this->memoryCache->set('bigger_cuckoo', 'bigger_cuckoo');
    $cids[2] = ['crow', FALSE];
    $cids[5] = ['bigger_cuckoo', 'bigger_cuckoo'];
    $this->assertCids($cids);

    // Confirm that setting the same item multiple times only uses one slot.
    $this->memoryCache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->memoryCache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->memoryCache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->memoryCache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->memoryCache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->assertCids($cids);

    // Confirm that deleting the same item multiple times only frees up one
    // slot.
    $this->memoryCache->delete('bigger_cuckoo');
    $this->memoryCache->delete('bigger_cuckoo');
    $this->memoryCache->delete('bigger_cuckoo');
    $this->memoryCache->delete('bigger_cuckoo');
    $this->memoryCache->delete('bigger_cuckoo');
    $this->memoryCache->delete('bigger_cuckoo');
    $this->memoryCache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->assertCids($cids);
    $this->memoryCache->set('crow', 'crow');
    $cids[2] = ['crow', 'crow'];
    $cids[1] = ['pidgin', FALSE];
    $this->assertCids($cids);
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
    $this->assertCids($cids);
    $this->memoryCache->invalidate('crow');
    $this->memoryCache->set('cuckoo', 'cuckoo', LruMemoryCache::CACHE_PERMANENT, ['cuckoo']);
    $cids[2] = ['crow', FALSE];
    $cids[] = ['cuckoo', 'cuckoo'];
    $this->assertCids($cids);
    $this->memoryCache->invalidateTags(['cuckoo']);
    $this->memoryCache->set('crow', 'crow');
    $cids[2] = ['crow', 'crow'];
    $cids[3] = ['cuckoo', FALSE];
    $this->assertCids($cids);

    $this->memoryCache->invalidateMultiple(['pidgin', 'crow']);
    $this->memoryCache->set('duck', 'duck');
    $cids[1] = ['pidgin', FALSE];
    $this->memoryCache->set('chicken', 'chicken');
    $cids[2] = ['crow', FALSE];
    $this->assertCids($cids);
  }

  /**
   * Assert that the given cache ID's match the given value in the memory cache.
   *
   * @param array $cids
   *   Array whose first element is the cache ID and whose second element is
   *   the value to check. When the second element is FALSE, this method will
   *   check that the cache ID is not present.
   */
  protected function assertCids(array $cids): void {
    foreach ($cids as $items) {
      $cached = $this->memoryCache->get($items[0]);
      if ($items[1]) {
        if ($cached) {
          $this->assertEquals($items[1], $cached->data, "$items[1] found in cache.");
        }
        else {
          $this->fail("$items[1] not found in cache.");
        }
      }
      else {
        $this->assertFalse($cached, "$items[1] not found in cache.");
      }
    }
  }

}
