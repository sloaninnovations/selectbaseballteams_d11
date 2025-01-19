<?php

namespace Drupal\Component\FileCache;

/**
 * Interface for file caches that support garbage collection.
 */
interface GarbageCollectionInterface {

  /**
   * Collects garbage.
   *
   * @return void
   */
  public function garbageCollection(): void;

}
