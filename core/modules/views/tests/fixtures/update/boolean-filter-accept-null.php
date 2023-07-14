<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

$connection->insert('config')
  ->fields([
    'collection' => '',
    'name' => 'views.view.node_link_update_test',
    'data' => serialize(Yaml::decode(file_get_contents(__DIR__ . '/views.view.node_link_update_test.yml'))),
  ])
  ->execute();
