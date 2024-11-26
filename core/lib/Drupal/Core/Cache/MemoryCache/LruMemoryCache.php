<?php

namespace Drupal\Core\Cache\MemoryCache;

use Drupal\Core\Cache\Cache;

/**
 * Defines a least recently used (LRU) static cache implementation.
 *
 * Stores cache items in memory using a PHP array. The number of cache items is
 * limited to a fixed number of slots. When the all slots are full, older items
 * are purged based on least recent usage.
 *
 * @ingroup cache
 */
class LruMemoryCache extends MemoryCache {

  /**
   * The number of slots allocated for items in the cache.
   *
   * @var int
   */
  protected $allowedSlots = 100;

  /**
   * Constructs an LruMemoryCache object.
   *
   * @param int $slots
   *   (optional) The number of slots to allocate for items in the cache.
   *   Defaults to 300.
   */
  public function __construct(int $slots = 300) {
    $this->allowedSlots = $slots;
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    if ($cached = parent::get($cid, $allow_invalid)) {
      // Move the item to the end of the array, so that it will be removed last.
      unset($this->cache[$cid]);
      $this->cache[$cid] = $cached;
    }
    return $cached;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = []) {
    // If the item is already in the cache, just move it to the first position.
    if (isset($this->cache[$cid])) {
      unset($this->cache[$cid]);
      parent::set($cid, $data, $expire, $tags);
    }
    parent::set($cid, $data, $expire, $tags);
    $diff = count($this->cache) - $this->allowedSlots;

    // Remove one item from the cache to ensure we remain within the allowed
    // number of slots. Avoid using array_slice() because it makes a copy of the
    // array, and avoid using array_splice() or array_shift() because they
    // re-index numeric keys.
    if ($diff > 0) {
      $first_key = array_key_first($this->cache);
      unset($this->cache[$first_key]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    parent::invalidate($cid);
    // Move the item to the least recently used position if it's not already
    // there. This cannot use array_unshift() because it would reindex an array
    // with numeric cache IDs.
    if (isset($this->cache[$cid])) {
      $item = $this->cache[$cid];
      $this->cache = [$cid => $item] + $this->cache;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {
    foreach ($cids as $cid) {
      $this->invalidate($cid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    foreach ($this->cache as $cid => $item) {
      if (array_intersect($tags, $item->tags)) {
        $this->invalidate($cid);
      }
    }
  }

}
