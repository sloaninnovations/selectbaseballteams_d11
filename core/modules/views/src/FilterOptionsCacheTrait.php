<?php

namespace Drupal\views;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageInterface;

/**
 * This trait provides a cache for filter options.
 */
trait FilterOptionsCacheTrait {

  /**
   * The cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $optionsCacheBin = NULL;

  /**
   * The cache id.
   *
   * @var string
   */
  protected $optionsCacheId = '';

  /**
   * The maximum age of the cache.
   *
   * @var int
   */
  protected $optionsMaxAge = Cache::PERMANENT;

  /**
   * The cache tags.
   *
   * @var array
   */
  protected $optionsCacheTags = [];

  /**
   * The cache contexts.
   *
   * @var array
   */
  protected $optionsCacheContexts = [];

  /**
   * Get the cache id based on view, display, filter, contexts and language.
   *
   * @return string
   *   The cache id.
   */
  public function optionsGetCacheId() {
    if (empty($this->optionsCacheId)) {
      $this->optionsCacheBin = \Drupal::cache('data');
      $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
      $key_data = ['default' => [$this->view->storage->id(), $this->displayHandler->display['id'], $this->field]];
      $key_data += \Drupal::service('cache_contexts_manager')->convertTokensToKeys($this->optionsCacheContexts)->getKeys();
      $this->optionsCacheAddTags($this->view->storage->getCacheTags());
      $this->optionsCacheId = 'views:options:' . hash('sha256', serialize($key_data)) . ':' . $langcode;
    }
    return $this->optionsCacheId;
  }

  /**
   * Add cache contexts to the filter options cache.
   *
   * @param array $cache_contexts
   *   The cache contexts to add.
   */
  public function optionsCacheAddContext(array $cache_contexts) {
    $this->optionsCacheContexts = Cache::mergeContexts($this->optionsCacheContexts, $cache_contexts);
  }

  /**
   * Add cache tags to the filter options cache.
   *
   * @param array $cache_tags
   *   The cache tags to add.
   */
  public function optionsCacheAddTags(array $cache_tags) {
    $this->optionsCacheTags = Cache::mergeTags($this->optionsCacheTags, $cache_tags);
  }

  /**
   * Retrieve options from cache.
   *
   * @return mixed
   *   The options from cache.
   */
  public function optionsCacheGet() {
    $cache_id = $this->optionsGetCacheId();
    $entry = $this->optionsCacheBin->get($cache_id);
    return ($entry) ? $entry->data : NULL;
  }

  /**
   * Add options to cache.
   *
   * @param mixed $options
   *   The options to cache.
   */
  public function optionsCacheSet(mixed $options) {
    $cache_id = $this->optionsGetCacheId();
    $this->optionsCacheBin->set($cache_id, $options, $this->optionsMaxAge, $this->optionsCacheTags);
  }

}
