<?php

declare(strict_types=1);

namespace Drupal\navigation\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the navigation module.
 */
class NavigationHooks {

  /**
   * Implements hook_navigation_menu_link_tree_alter().
   */
  #[Hook('navigation_menu_link_tree_alter')]
  public function navigationMenuLinkTreeAlter(array &$tree): void {
    foreach ($tree as $key => $item) {
      // Skip elements where menu is not the 'admin' one.
      $menu_name = $item->link->getMenuName();
      if ($menu_name != 'admin') {
        continue;
      }

      // Remove unwanted Help and Content menu links.
      $plugin_id = $item->link->getPluginId();
      if ($plugin_id == 'help.main' || $plugin_id == 'system.admin_content') {
        unset($tree[$key]);
      }

      // Remove child items of content menu, if any.
      $parent = $item->link->getParent();
      if ($parent == 'system.admin_content') {
        unset($tree[$key]);
      }
    }
  }

}
