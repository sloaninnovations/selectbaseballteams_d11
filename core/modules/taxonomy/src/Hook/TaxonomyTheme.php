<?php

namespace Drupal\taxonomy\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for taxonomy.
 */
class TaxonomyTheme {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return ['taxonomy_term' => ['render element' => 'elements']];
  }

}
