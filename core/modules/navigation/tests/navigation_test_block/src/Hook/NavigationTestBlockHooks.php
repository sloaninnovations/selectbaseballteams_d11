<?php

declare(strict_types=1);

namespace Drupal\navigation_test_block\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;

/**
 * Hooks implementations for navigation_test_block module.
 */
class NavigationTestBlockHooks {

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
    $definitions['navigation_test']['allow_in_navigation'] = TRUE;
  }

  /**
   * Implements hook_navigation_defaults().
   */
  #[Hook('navigation_defaults')]
  public function navigationDefaults(): array {
    $blocks = [];

    $blocks[] = [
      'delta' => 1,
      'configuration' => [
        'id' => 'navigation_test',
        'label' => 'My test block',
        'label_display' => 1,
        'provider' => 'navigation_test_block',
      ],
    ];

    return $blocks;
  }

}
