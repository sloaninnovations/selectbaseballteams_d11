<?php

/**
 * @file
 * Post update functions for Field module.
 */

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\field\Entity\FieldConfig;

/**
 * Implements hook_removed_post_updates().
 */
function field_removed_post_updates(): array {
  return [
    'field_post_update_save_custom_storage_property' => '9.0.0',
    'field_post_update_entity_reference_handler_setting' => '9.0.0',
    'field_post_update_email_widget_size_setting' => '9.0.0',
    'field_post_update_remove_handler_submit_setting' => '9.0.0',
  ];
}

/**
 * Update the default plugins for entity reference selection handler plugins.
 */
function field_post_update_add_entity_reference_selection_plugin_option(): void {
  foreach (FieldConfig::loadMultiple() as $field_config) {
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $class = $field_type_manager->getPluginClass($field_config->getType());
    if ($class === EntityReferenceItem::class || is_subclass_of($class, EntityReferenceItem::class)) {
      $settings = $field_config->getSettings();
      $settings['handler_settings']['include_unpublished_entities'] = FALSE;
      $field_config->setSettings($settings);
      $field_config->save();
    }
  }
}
