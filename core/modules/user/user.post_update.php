<?php

/**
 * @file
 * Post update functions for User module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function user_removed_post_updates(): array {
  return [
    'user_post_update_enforce_order_of_permissions' => '9.0.0',
    'user_post_update_update_roles' => '10.0.0',
    'user_post_update_sort_permissions' => '11.0.0',
    'user_post_update_sort_permissions_again' => '11.0.0',
  ];
}

/**
 * Update hook to add user_login_method data to user settings config .
 */
function user_add_user_login_method_to_user_settings_config(): void {
  $config = \Drupal::configFactory()->getEditable('user.settings');
  $config->set('user_login_method', 'username_only');
  $config->save();
}
