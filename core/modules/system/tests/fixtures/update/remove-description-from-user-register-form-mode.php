<?php

/**
 * @file
 * Empties the description of the user register form mode.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$data = $connection->select('config')
  ->condition('name', 'core.entity_form_mode.user.register')
  ->fields('config', ['data'])
  ->execute()
  ->fetchField();
$data = unserialize($data);
$data['description'] = "\n";
$connection->update('config')
  ->condition('name', 'core.entity_form_mode.user.register')
  ->fields([
    'data' => serialize($data),
  ])
  ->execute();
