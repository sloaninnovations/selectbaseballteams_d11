<?php

namespace Drupal\file;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;

/**
 * File Migration Dependency Manager.
 *
 * Manages file migration dependencies.
 */
class FileMigrationDependencyManager implements FileMigrationDependencyManagerInterface {

  use MigrationDeriverTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The migration field plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface
   */
  protected $migrateFieldPluginManager;

  /**
   * Constructs FileMigrationDependencyManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Sets the migration field plugin manager.
   *
   * @param \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface $migrate_field_plugin_manager
   *   The migration field plugin manager.
   */
  public function setMigrateFieldPluginManager(MigrateFieldPluginManagerInterface $migrate_field_plugin_manager) {
    $this->migrateFieldPluginManager = $migrate_field_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function addFileMigrationDependencies(array &$migrations, array $field_migration_plugin_ids) {
    if (!$this->migrateFieldPluginManager) {
      // Migrate field plugin manager is not available.
      return;
    }

    $field_plugin_instances = [];

    foreach ($field_migration_plugin_ids as $field_migration_plugin_id) {
      try {
        $field_plugin_instance = $this->migrateFieldPluginManager->createInstance($field_migration_plugin_id);
        if ($field_plugin_instance instanceof FieldPluginBase) {
          $field_plugin_instances[$field_migration_plugin_id] = $field_plugin_instance;
        }
      }
      catch (PluginException $e) {
        // Plugin cannot be instantiated.
      }
    }

    // None of the plugins is a migrate field plugin instance.
    if (empty($field_plugin_instances)) {
      return;
    }

    $field_plugin_classes = array_reduce($field_plugin_instances, function ($carry, $plugin_instance) {
      $carry[] = get_class($plugin_instance);
      return $carry;
    }, []);
    $entity_destination_plugins = ['entity', 'entity_complete'];
    $migrate_field_plugin_manager = $this->migrateFieldPluginManager;

    foreach ($migrations as &$migration_plugin) {
      $required_file_migration_plugins = [];

      // Skip if we cannot determine source Drupal version from migration tags
      // or if the migration plugin is not a Drupal 7 migration.
      if (!isset($migration_plugin['migration_tags']) || !is_array($migration_plugin['migration_tags']) || !in_array('Drupal 7', $migration_plugin['migration_tags'], TRUE)) {
        continue;
      }

      // When using the `file_entity` contributed module, it's possible that
      // fields are configured for the File entities, which will cause every
      // migration with `entity:file` as the destination plugin to get a
      // dependency on `d7_file` and/or `d7_file_private`. That makes no sense
      // of course: protect against this edge case.
      if ($migration_plugin['id'] === 'd7_file' || $migration_plugin['id'] === 'd7_file_private') {
        continue;
      }

      $destination_plugin = $migration_plugin['destination']['plugin'] ?? [];
      $destination_entity_type_id = in_array(explode(':', $destination_plugin)[0], $entity_destination_plugins, TRUE) && count(explode(':', $destination_plugin)) === 2 ?
        explode(':', $destination_plugin)[1] : NULL;

      // Skip if the destination is not an entity OR the destination is not an
      // available entity type (d7_node_translation with disabled node module).
      if (!$destination_entity_type_id || !$definition = $this->entityTypeManager->getDefinition($destination_entity_type_id, FALSE)) {
        continue;
      }
      // Skip if the destination is not a content entity.
      if (!$definition instanceof ContentEntityTypeInterface) {
        continue;
      }
      // Skip users: we don't want to make user migration depend on file
      // migrations.
      if ($destination_entity_type_id === 'user') {
        continue;
      }

      try {
        $source = $this->getSourcePlugin($migration_plugin['source']['plugin']);
        if (!$source instanceof DrupalSqlBase) {
          throw new \LogicException('Not a Drupal to Drupal migration');
        }
        $destination_bundle = $migration_plugin['source']['node_type'] ?? $migration_plugin['source']['bundle'] ?? NULL;

        $query = $source->getDatabase()->select('field_config_instance', 'fci')
          ->fields('fci', [])
          ->condition('fci.deleted', 0)
          ->condition('fci.entity_type', $destination_entity_type_id);

        if ($destination_bundle) {
          $query->condition('fci.bundle', $destination_bundle);
        }

        $field_storage_table_alias = $query->join('field_config', 'fc', 'fci.field_id = %alias.id');
        $field_storage_data_column_alias = $query->addField($field_storage_table_alias, 'data', 'fc_data');

        $query->fields($field_storage_table_alias, ['type'])
          ->condition($field_storage_table_alias . '.active', 1)
          ->condition($field_storage_table_alias . '.storage_active', 1)
          ->condition($field_storage_table_alias . '.deleted', 0);

        $required_file_migration_plugins = array_reduce($query->execute()->fetchAllAssoc('id'), function ($dependencies, $row) use ($field_storage_data_column_alias, $migrate_field_plugin_manager, $field_plugin_classes) {
          $field_storage_data = unserialize($row->$field_storage_data_column_alias);

          try {
            $field_migration_plugin_id = $migrate_field_plugin_manager->getPluginIdFromFieldType($row->type, ['core' => 7]);
            $field_migration_plugin_instance = $migrate_field_plugin_manager->createInstance($field_migration_plugin_id);
            $field_is_file_type = array_reduce($field_plugin_classes, function ($carry, $field_plugin_class) use ($field_migration_plugin_instance) {
              if (!$carry) {
                $carry = $field_migration_plugin_instance instanceof $field_plugin_class;
              }
              return $carry;
            }, FALSE);
          }
          catch (\Exception $e) {
            return $dependencies;
          }

          // Plugin is not an instance of the given field plugin class.
          if (!$field_is_file_type) {
            return $dependencies;
          }

          $file_field_scheme = $field_storage_data['settings']['uri_scheme'] ?? NULL;

          // Add migration dependency metadata for public files.
          if ($file_field_scheme === 'public') {
            $file_migration_dependency_is_missing = array_search('d7_file', $dependencies) === FALSE;

            if ($file_migration_dependency_is_missing) {
              $dependencies[] = 'd7_file';
            }
          }
          // Add migration dependency metadata for private file migration.
          elseif ($file_field_scheme === 'private') {
            $private_file_migration_dependency_is_missing = array_search('d7_file_private', $dependencies) === FALSE;

            if ($private_file_migration_dependency_is_missing) {
              $dependencies[] = 'd7_file_private';
            }
          }

          return $dependencies;
        }, []);
      }
      catch (\Exception $exception) {
        continue;
      }

      if (!empty($required_file_migration_plugins)) {
        $migration_plugin['migration_dependencies']['required'] = array_unique(
          array_merge(
            $migration_plugin['migration_dependencies']['required'],
            $required_file_migration_plugins
          )
        );
      }
    }
  }

}
