<?php

namespace Drupal\Core\Cache;

/**
 * Collects a chain of cache clearers to be called in priority order.
 */
class ChainCacheClearer implements CacheClearerInterface {

  /**
   * The cache clearers, keyed by priority.
   *
   * @var array
   */
  protected array $cacheClearers = [];

  /**
   * The sorted cache clearers.
   *
   * @var \Drupal\Core\Cache\CacheClearerInterface[]|null
   */
  protected ?array $sortedCacheClearers = NULL;

  /**
   * Adds a cache clearer.
   *
   * @param \Drupal\Core\Cache\CacheClearerInterface $cacheClearer
   *   The cache clearer.
   * @param int $priority
   *   The priority.
   */
  public function add(CacheClearerInterface $cacheClearer, int $priority = 0): void {
    $this->cacheClearers[$priority][] = $cacheClearer;
    // Clear the sorted list to trigger a re-sort.
    $this->sortedCacheClearers = [];
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache(): void {
    if ($this->sortedCacheClearers == NULL) {
      $this->sortedCacheClearers = $this->getSortedCacheClearers();
    }
    foreach ($this->sortedCacheClearers as $cacheClearer) {
      $cacheClearer->clearCache();
    }
  }

  /**
   * Gets the cache clearers sorted in priority order.
   *
   * @return array
   *   The sorted cache clearers.
   */
  protected function getSortedCacheClearers(): array {
    krsort($this->cacheClearers);
    return array_merge(...$this->cacheClearers);
  }

}
