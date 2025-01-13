<?php

/**
 * @file
 * Post update functions for File.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\file\FileConfigUpdater;

/**
 * Implements hook_removed_post_updates().
 */
function file_removed_post_updates(): array {
  return [
    'file_post_update_add_txt_if_allows_insecure_extensions' => '10.0.0',
    'file_post_update_add_permissions_to_roles' => '11.0.0',
    'file_post_update_add_default_filename_sanitization_configuration' => '11.0.0',
  ];
}

/**
 * Add the preload configuration to existing media formatters.
 */
function file_post_update_preload_setting(array &$sandbox = NULL): void {
  $file_config_updater = \Drupal::classResolver(FileConfigUpdater::class);
  assert($file_config_updater instanceof FileConfigUpdater);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_view_display', function (EntityViewDisplayInterface $view_display) use ($file_config_updater): bool {
    return $file_config_updater->processPreloadSetting($view_display);
  });
}
