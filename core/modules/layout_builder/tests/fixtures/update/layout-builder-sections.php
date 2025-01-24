<?php

/**
 * @file
 * Test context mapping update path by adding a layout without a context map.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Add a layout plugin to an existing entity view display.
$display = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.article.teaser')
  ->execute()
  ->fetchField();
$display = unserialize($display);
$display['dependencies']['module'][] = 'layout_builder';
$display['dependencies']['module'][] = 'layout_discovery';
$display['third_party_settings']['layout_builder']['allow_custom'] = FALSE;
$display['third_party_settings']['layout_builder']['enabled'] = TRUE;
$display['third_party_settings']['layout_builder']['sections'][] = [
  'layout_id' => 'layout_onecol',
  'layout_settings' => ['label' => ''],
  'components' => [
    '92bf8983-64cc-4f7d-b8c5-1ff9c6a5d7dc' => [
      'uuid' => '92bf8983-64cc-4f7d-b8c5-1ff9c6a5d7dc',
      'region' => 'content',
      'configuration' => [
        'id' => 'field_block:node:article:field_image',
        'label_display' => '0',
        'context_mapping' => [
          'entity' => 'layout_builder.entity',
        ],
        'formatter' => [
          'type' => 'image',
          'label' => 'hidden',
          'settings' => [
            'image_link' => '',
            'image_style' => 'wide',
            'image_loading' => [
              'attribute' => 'eager',
            ],
          ],
          'third_party_settings' => [],
        ],
      ],
      'weight' => 0,
      'additional' => [
        'key' => 'value',
        'another_key' => 'another_value',
      ],
      'third_party_settings' => [
        'layout_builder_defaults_test' => [
          'harold' => 'maude',
        ],
      ],
    ],
    '2b3961a0-1c6f-4264-b01f-525a23e8c2b6' => [
      'uuid' => '2b3961a0-1c6f-4264-b01f-525a23e8c2b6',
      'region' => 'content',
      'configuration' => [
        'id' => 'field_block:node:article:body',
        'label_display' => '0',
        'context_mapping' => [
          'entity' => 'layout_builder.entity',
        ],
        'formatter' => [
          'type' => 'text_default',
          'label' => 'hidden',
          'settings' => [],
          'third_party_settings' => [],
        ],
      ],
      'weight' => 1,
      'additional' => [],
    ],
  ],
  'third_party_settings' => [],
];
$connection->update('config')
  ->fields(['data' => serialize($display)])
  ->condition('collection', '')
  ->condition('name', 'core.entity_view_display.node.article.teaser')
  ->execute();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['layout_builder_defaults_test'] = 0;
$connection->update('config')
  ->fields(['data' => serialize($extensions)])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();
