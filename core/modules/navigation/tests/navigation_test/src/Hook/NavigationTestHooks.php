<?php

declare(strict_types=1);

namespace Drupal\navigation_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;

/**
 * Hooks implementations for navigation_test module.
 */
class NavigationTestHooks {

  /**
   * NavigationTestHooks constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(
    protected StateInterface $state,
  ) {
  }

  /**
   * Implements hook_block_alter().
   */
  #[Hook('block_alter')]
  public function blockAlter(&$definitions): void {
    if ($this->state->get('navigation_safe_alter')) {
      $definitions['navigation_link']['allow_in_navigation'] = TRUE;
      $definitions['navigation_shortcuts']['allow_in_navigation'] = FALSE;
    }
  }

  /**
   * Implements hook_navigation_menu_link_tree_alter().
   */
  #[Hook('navigation_menu_link_tree_alter')]
  public function navigationMenuLinkTreeAlter(array &$tree): void {
    foreach ($tree as $key => $item) {
      // Skip elements where menu is not the 'admin' one.
      $menu_name = $item->link->getMenuName();
      // Removes all items from menu1.
      if ($menu_name == 'menu1') {
        unset($tree[$key]);
      }

      // Updates title for items in menu2
      if ($menu_name == 'menu2') {
        $item->link->updateLink(['title' => 'New Link Title'], FALSE);
      }
    }
  }

}
