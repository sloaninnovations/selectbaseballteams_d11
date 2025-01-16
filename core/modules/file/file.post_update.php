<?php

/**
 * @file
 * Post update functions for File.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\file\FileConfigUpdater;
use Drupal\user\RoleInterface;

/**
 * Implements hook_removed_post_updates().
 */
function file_removed_post_updates() {
  return [
    'file_post_update_add_txt_if_allows_insecure_extensions' => '10.0.0',
  ];
}

/**
 * Grant all non-anonymous roles the 'delete own files' permission.
 */
function file_post_update_add_permissions_to_roles(?array &$sandbox = NULL): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'user_role', function (RoleInterface $role): bool {
    if ($role->id() === RoleInterface::ANONYMOUS_ID || $role->isAdmin()) {
      return FALSE;
    }
    $role->grantPermission('delete own files');
    return TRUE;
  });
}

/**
 * Add default filename sanitization configuration.
 */
function file_post_update_add_default_filename_sanitization_configuration() {
  $config = \Drupal::configFactory()->getEditable('file.settings');
  $config->set('filename_sanitization.transliterate', FALSE);
  $config->set('filename_sanitization.replace_whitespace', FALSE);
  $config->set('filename_sanitization.replace_non_alphanumeric', FALSE);
  $config->set('filename_sanitization.deduplicate_separators', FALSE);
  $config->set('filename_sanitization.lowercase', FALSE);
  $config->set('filename_sanitization.replacement_character', '-');
  $config->save();
}

/**
 * Add the preload configuration to existing media formatters.
 */
function file_post_update_preload_setting(?array &$sandbox = NULL): void {
  $file_config_updater = \Drupal::classResolver(FileConfigUpdater::class);
  assert($file_config_updater instanceof FileConfigUpdater);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_view_display', function (EntityViewDisplayInterface $view_display) use ($file_config_updater): bool {
    return $file_config_updater->processPreloadSetting($view_display);
  });
}
