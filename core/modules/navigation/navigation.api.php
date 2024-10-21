<?php

/**
 * @file
 * Hooks specific to the Navigation module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the content of a given Navigation menu link tree.
 *
 * @param array &$tree
 *   The Navigation link tree.
 *
 * @see \Drupal\navigation\Menu\NavigationMenuLinkTree::transform()
 */
function hook_navigation_menu_link_tree_alter(array &$tree): void {
  foreach ($tree as $key => $item) {
    // Skip elements where menu is not the 'admin' one.
    $menu_name = $item->link->getMenuName();
    if ($menu_name != 'admin') {
      continue;
    }

    // Remove unwanted Help menu link.
    $plugin_id = $item->link->getPluginId();
    if ($plugin_id == 'help.main') {
      unset($tree[$key]);
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
