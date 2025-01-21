<?php

namespace Drupal\file;

/**
 * Interface for FileMigrationDependencyManager.
 */
interface FileMigrationDependencyManagerInterface {

  /**
   * Adds file migration as required dependency.
   *
   * Adds public or private file migration requirement to those content entity
   * migrations that need them.
   *
   * @param array[] $migrations
   *   An associative array of migrations keyed by migration ID, the same that
   *   is passed to hook_migration_plugins_alter() hooks.
   * @param string[] $field_migration_plugin_ids
   *   An array of file field migration plugin IDs.
   */
  public function addFileMigrationDependencies(array &$migrations, array $field_migration_plugin_ids);

}
