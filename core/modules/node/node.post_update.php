<?php

/**
 * @file
 * Post update functions for Node.
 */

/**
 * Implements hook_removed_post_updates().
 */
function node_removed_post_updates(): array {
  return [
    'node_post_update_configure_status_field_widget' => '9.0.0',
    'node_post_update_node_revision_views_data' => '9.0.0',
    'node_post_update_glossary_view_published' => '10.0.0',
    'node_post_update_rebuild_node_revision_routes' => '10.0.0',
    'node_post_update_modify_base_field_author_override' => '10.0.0',
    'node_post_update_set_node_type_description_and_help_to_null' => '11.0.0',
  ];
}

/**
 * Remove 'not null' constraint from node title column.
 */
function node_post_update_remove_title_not_null_constraint(): void {
  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();

  $field_storage_definition = $definition_update_manager->getFieldStorageDefinition('title', 'node');
  $field_storage_definition->setStorageRequired(FALSE);

  $definition_update_manager->updateFieldStorageDefinition($field_storage_definition);
}
