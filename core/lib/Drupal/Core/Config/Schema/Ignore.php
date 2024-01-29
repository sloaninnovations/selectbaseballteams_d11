<?php

namespace Drupal\Core\Config\Schema;

/**
 * Configuration property to ignore.
 */
class Ignore extends Element {

  /**
   * {@inheritdoc}
   */
  public function getCanonicalRepresentation(bool $suppress_exceptions = FALSE): mixed {
    // This element has no schema, so no normalization is possible.
    return $this->value;
  }

}
