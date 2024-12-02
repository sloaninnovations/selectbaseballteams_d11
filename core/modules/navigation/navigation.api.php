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
 * Provides default content for the Navigation bar.
 *
 * @return array
 *   An associative array of navigation block definitions.
 */
function hook_navigation_defaults(): array {
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

/**
 * @} End of "addtogroup hooks".
 */
