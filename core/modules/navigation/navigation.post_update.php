<?php

/**
 * @file
 * Post update functions for the Navigation module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\user\RoleInterface;

/**
 * Grants navigation specific permission to roles with access to any layout.
 */
function navigation_post_update_update_permissions(array &$sandbox) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'user_role', function (RoleInterface $role) {
    $needs_save = FALSE;
    if ($role->hasPermission('configure any layout')) {
      $role->grantPermission('configure navigation layout');
      $needs_save = TRUE;
    }
    if ($role->hasPermission('administer navigation_block')) {
      $role->revokePermission('administer navigation_block');
      $role->grantPermission('configure navigation layout');
      $needs_save = TRUE;
    }
    return $needs_save;
  });
}

/**
 * Defines the values for the default logo dimensions.
 */
function navigation_post_update_set_logo_dimensions_default(array &$sandbox) {
  // Empty post_update hook.
}

/**
 * Flushes tempstore repository for navigation to reflect definition changes.
 */
function navigation_post_update_refresh_tempstore_repository(array &$sandbox): void {
  /** @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager */
  $section_storage_manager = \Drupal::service(SectionStorageManagerInterface::class);
  /** @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository */
  $layout_tempstore_repository = \Drupal::service(LayoutTempstoreRepositoryInterface::class);

  $section_storage = $section_storage_manager->loadEmpty('navigation');
  $layout_tempstore_repository->delete($section_storage);
}
