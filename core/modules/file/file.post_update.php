<?php

/**
 * @file
 * Post update functions for File.
 */

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
