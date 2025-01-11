<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\ContentEntity as MigrateContentEntity;

/**
 * Source plugin to get content entities from the current version of Drupal.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
 * \Drupal\migrate\Plugin\migrate\source\ContentEntity instead.
 *
 * @see https://www.drupal.org/node/3498916
 */
class ContentEntity extends MigrateContentEntity {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use \Drupal\migrate\Plugin\migrate\source\ContentEntity instead. See https://www.drupal.org/node/3498916', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $entity_type_manager, $entity_field_manager, $entity_type_bundle_info);
  }

}
