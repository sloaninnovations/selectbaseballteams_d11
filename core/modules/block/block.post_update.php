<?php

/**
 * @file
 * Post update functions for Block.
 */

use Drupal\block\BlockInterface;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;

/**
 * Implements hook_removed_post_updates().
 */
function block_removed_post_updates() {
  return [
    'block_post_update_disable_blocks_with_missing_contexts' => '9.0.0',
    'block_post_update_disabled_region_update' => '9.0.0',
    'block_post_update_fix_negate_in_conditions' => '9.0.0',
    'block_post_update_replace_node_type_condition' => '10.0.0',
  ];
}

/**
 * Updates all blocks with new settings for condition logic.
 */
function block_post_update_move_custom_block_library(&$sandbox = NULL): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'block', function (BlockInterface $block) {
    $settings = $block->get('settings');
    if (!isset($settings['condition_logic'])) {
      $settings['condition_logic'] = 'and';
      $block->set('settings', $settings);
      return TRUE;
    }
    return FALSE;
  });
}
