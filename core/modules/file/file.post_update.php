<?php

/**
 * @file
 * Post update functions for File.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
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

function file_post_update_add_poster_image() {
  // Load all "file" field storage instances:
  $fieldInstances = \Drupal::entityTypeManager()
    ->getStorage('field_config')
    ->loadByProperties(['field_type' => 'file']);

  foreach ($fieldInstances as $fieldInstance) {
    $properties = [
      'targetEntityType' => $fieldInstance->getEntityTypeId(),
      'bundle' => $fieldInstance->bundle(),
    ];
    // @todo Only get form displays, using the "file_video" formatter here:
    if ($form_displays = \Drupal::entityTypeManager()->getStorage('entity_form_display')->loadByProperties($properties)) {
      foreach ($form_displays as $form_display) {
        if ($component = $form_display->getComponent($fieldInstance->getName())) {
          $form_display->setComponent($fieldInstance->getName(), array_merge_recursive(
            $component,
            [
              'settings' => [
                'poster' => '',
                'poster_image_style' => '',
              ],
            ]))->save();
        }
      }
    }
  }
}
