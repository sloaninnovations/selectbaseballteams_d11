<?php

namespace Drupal\Core\Controller;

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Allows attaching a cacheability metadata to a title.
 */
class CacheableTitle extends BubbleableMetadata {

  /**
   * The title.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup|string|null
   */
  protected $title;

  /**
   * Gets the title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|null
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Sets the title.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string|null $title
   *   The title.
   */
  public function setTitle($title) {
    $this->title = $title;
  }

}
