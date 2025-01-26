<?php

/**
 * @file
 * Post update functions for Path Alias.
 */

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;

/**
 * Implements hook_removed_post_updates().
 */
function path_alias_removed_post_updates(): array {
  return [
    'path_alias_post_update_drop_path_alias_status_index' => '11.0.0',
  ];
}

/**
 * Update the path_alias_revision indices.
 */
function path_alias_post_update_update_path_alias_revision_indexes(): void {
  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
  $update_manager = \Drupal::service('entity.definition_update_manager');
  $entity_type = $update_manager->getEntityType('path_alias');
  $update_manager->updateEntityType($entity_type);
}

/**
 * Update entity definitions, necessary if notices appear on site status page.
 */
function path_alias_post_update_entity_updates(): void {
  $entityDefinitionUpdateManager = \Drupal::service('entity.definition_update_manager');
  $entityTypeManager = \Drupal::service('entity_type.manager');
  $entityFieldManager = \Drupal::service('entity_field.manager');
  $entityLastInstalledSchemaRepository = \Drupal::service('entity.last_installed_schema.repository');
  $fieldStorageDefinitionListener = \Drupal::service('field_storage_definition.listener');
  $entityTypeListener = \Drupal::service('entity_type.listener');
  $reflector = new \ReflectionMethod($entityDefinitionUpdateManager, 'getChangeList');
  $reflector->setAccessible(TRUE);
  $complete_change_list = $reflector->invoke($entityDefinitionUpdateManager);
  if ($complete_change_list) {
    // EntityDefinitionUpdateManagerInterface::getChangeList() only disables
    // the cache and does not invalidate. In case there are changes,
    // explicitly invalidate caches.
    $entityTypeManager->clearCachedDefinitions();
    $entityFieldManager->clearCachedFieldDefinitions();
  }
  $entity_type_id = 'path_alias';
  $change_list = $complete_change_list[$entity_type_id];
  $entity_type = $entityTypeManager->getDefinition($entity_type_id);
  switch ($change_list['entity_type']) {
    case EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED:
      $entityTypeListener->onEntityTypeCreate($entity_type);
      break;

    case EntityDefinitionUpdateManagerInterface::DEFINITION_UPDATED:
      $original = $entityLastInstalledSchemaRepository->getLastInstalledDefinition($entity_type_id);
      $field_storage_definitions = $entityFieldManager->getFieldStorageDefinitions($entity_type_id);
      $original_field_Storage_definitions = $entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type_id);
      $entityTypeListener->onFieldableEntityTypeUpdate($entity_type, $original, $field_storage_definitions, $original_field_Storage_definitions);
      break;
  }
  // Process field storage definition changes.
  if (!empty($change_list['field_storage_definitions'])) {
    $storage_definitions = $entityFieldManager->getFieldStorageDefinitions('path_alias');
    $original_storage_definitions = $entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions('path_alias');
    foreach ($change_list['field_storage_definitions'] as $field_name => $op) {
      $storage_definition = $storage_definitions[$field_name] ?? NULL;
      $original_storage_definition = $original_storage_definitions[$field_name] ?? NULL;
      switch ($op) {
        case EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED:
          $fieldStorageDefinitionListener->onFieldStorageDefinitionCreate($storage_definition);
          break;

        case EntityDefinitionUpdateManagerInterface::DEFINITION_UPDATED:
          if ($storage_definition && $original_storage_definition) {
            $fieldStorageDefinitionListener->onFieldStorageDefinitionUpdate($storage_definition, $original_storage_definition);
          }
          break;

        case EntityDefinitionUpdateManagerInterface::DEFINITION_DELETED:
          if ($original_storage_definition) {
            $fieldStorageDefinitionListener->onFieldStorageDefinitionDelete($original_storage_definition);
          }
          break;
      }
    }
  }
}
