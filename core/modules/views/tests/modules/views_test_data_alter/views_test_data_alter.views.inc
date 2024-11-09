<?php

declare(strict_types=1);

/**
 * @file
 * Provides views data alter isolated hook.
 */

/**
 * Implements hook_views_data_alter().
 */
function views_test_data_alter_views_data_alter(array &$data) {
  // Modify a filter to use a different filter handler plugin
  // by default so that we can test that the handler used
  // respects the handler plugin ID specified in the
  // configuration.
  $data['node_field_data']['status']['filter']['id'] = 'numeric';
}
