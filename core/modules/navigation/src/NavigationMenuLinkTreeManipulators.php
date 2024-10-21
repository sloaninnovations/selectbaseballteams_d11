<?php

declare(strict_types=1);

namespace Drupal\navigation;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides a menu link tree manipulators.
 *
 *  This class provides menu link tree manipulators to:
 *  - trigger generic navigation link tree manipulate event,
 */
final class NavigationMenuLinkTreeManipulators {

  /**
   * Constructs a NavigationMenuLinkTreeManipulators object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    private readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * @todo Add method description.
   */
  public function manipulate(array $tree): array {
    $this->moduleHandler->alter('navigation_menu_link_tree_manipulators', $tree);

    return $tree;
  }

}
