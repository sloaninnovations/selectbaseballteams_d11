<?php

/**
 * @file
 * Fixture file to test file_post_update_add_playsinline().
 *
 * @see https://www.drupal.org/project/drupal/issues/3046152
 * @see \Drupal\Tests\file\Functional\Formatter\FileVideoFormatterUpdateTest
 * @see \file_post_update_add_playsinline()
 */

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Database\Database;

$display = Yaml::decode(file_get_contents(__DIR__ . '/post_update_playsinline-3046152.yml'));

$db = Database::getConnection();
$db->insert('config')
  ->fields([
    'name' => 'core.entity_view_display.media.video.default',
    'data' => serialize($display),
  ])
  ->execute();
