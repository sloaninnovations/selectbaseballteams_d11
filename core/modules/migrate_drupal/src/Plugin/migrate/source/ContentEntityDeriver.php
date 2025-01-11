<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Plugin\migrate\source\ContentEntityDeriver as MigrateContentEntityDeriver;

/**
 * Deriver for content entity source plugins.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
 * \Drupal\migrate\Plugin\migrate\source\ContentEntityDeriver instead.
 *
 * @see https://www.drupal.org/node/3498916
 */
class ContentEntityDeriver extends MigrateContentEntityDeriver {

  /**
   * Constructs a new ContentEntityDeriver.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entityTypeManager) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use \Drupal\migrate\Plugin\migrate\source\ContentEntity instead. See https://www.drupal.org/node/3498916', E_USER_DEPRECATED);
    parent::__construct($base_plugin_id, $entityTypeManager);
  }

}
