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
 * Provide content for Navigation promoted section.
 *
 * @return array
 *   An associative array of renderable elements.
 *
 * @see hook_navigation_content_top_alter()
 */
function hook_navigation_content_top(): array {
  return [
    'navigation_foo' => [
      '#markup' => 'foo',
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
 * @param $promoted
 *   An associative array of content returned by hook_navigation_content_top().
 *
 * @see hook_navigation_content_top()
 */
function hook_navigation_content_top_alter(array &$promoted): void {
  // Remove a specific element.
  unset($promoted['navigation_foo']);
  // Modify an element.
  $promoted['navigation_bar']['#markup'] = 'new bar';
  // Change weight.
  $promoted['navigation_baz']['#weight'] = '-100';
}

/**
 * @} End of "addtogroup hooks".
 */
