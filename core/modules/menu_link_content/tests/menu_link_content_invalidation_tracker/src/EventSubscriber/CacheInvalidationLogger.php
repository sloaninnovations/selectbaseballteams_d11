<?php

declare(strict_types=1);

namespace Drupal\menu_link_content_invalidation_tracker\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Logs cache invalidations.
 */
class CacheInvalidationLogger implements CacheTagsInvalidatorInterface {

  /**
   * Array of invalidated tags.
   */
  protected array $invalidatedTags = [];

  /**
   * Constructs a new instance of CacheInvalidationLogger.
   */
  public function __construct(protected CacheTagsInvalidator $inner) {}

  /**
   * Records invalidated tags before passing to decorated service.
   */
  public function invalidateTags(array $tags): void {
    $this->invalidatedTags = array_merge($this->invalidatedTags, $tags);
    $this->inner->invalidateTags($tags);
  }

  /**
   * Delegates to inner resetChecksums method.
   */
  public function resetChecksums(): void {
    $this->inner->resetChecksums();
  }

  /**
   * Reset the invalidated tags.
   */
  public function resetInvalidatedTags(): void {
    $this->invalidatedTags = [];
  }

  /**
   * Get the invalidated tags.
   */
  public function getInvalidatedTags(): array {
    return $this->invalidatedTags;
  }

}
