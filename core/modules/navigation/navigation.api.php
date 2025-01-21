<?php

/**
 * @file
 * Hooks related to the Navigation module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide content for Navigation content_top section.
 *
 * @return array
 *   An associative array of renderable elements.
 *
 * @see hook_navigation_content_top_alter()
 */
function hook_navigation_content_top(): array {
  return [
    'navigation_foo' => [
      '#markup' => \Drupal::config('system.site')->get('name'),
      '#cache' => [
        'tags' => ['config:system.site'],
      ],
    ],
    'navigation_bar' => [
      '#markup' => 'bar',
    ],
    'navigation_baz' => [
      '#markup' => 'baz',
    ],
  ];
}

/**
 * Alter replacement values for placeholder tokens.
 *
 * @param $content_top
 *   An associative array of content returned by hook_navigation_content_top().
 *
 * @see hook_navigation_content_top()
 */
function hook_navigation_content_top_alter(array &$content_top): void {
  // Remove a specific element.
  unset($content_top['navigation_foo']);
  // Modify an element.
  $content_top['navigation_bar']['#markup'] = 'new bar';
  // Change weight.
  $content_top['navigation_baz']['#weight'] = '-100';
}

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
