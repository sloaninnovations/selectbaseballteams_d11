<?php

namespace Drupal\text\Plugin\migrate\field\d7;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

#[MigrateField(
  id: 'd7_text',
  core: [7],
  type_map: [
    'text' => 'text',
    'text_long' => 'text_long',
    'text_with_summary' => 'text_with_summary',
  ],
  source_module: 'text',
  destination_module: 'text',
)]
class TextField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterType(Row $row) {
    $field_type = $this->getFieldType($row);
    $formatter_type = $row->getSourceProperty('formatter/type');

    switch ($field_type) {
      case 'string':
        $formatter_type = str_replace(['text_default', 'text_plain'], 'string', $formatter_type);
        break;

      case 'string_long':
        $formatter_type = str_replace(['text_default', 'text_plain'], 'basic_string', $formatter_type);
        break;
    }

    return $formatter_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetType(Row $row) {
    $field_type = $this->getFieldType($row);
    $widget_type = $row->getSourceProperty('widget/type');

    switch ($field_type) {
      case 'string':
        $widget_type = str_replace('text_textfield', 'string_textfield', $widget_type);
        break;

      case 'string_long':
        $widget_type = str_replace('text_textarea', 'string_textarea', $widget_type);
        break;
    }

    return $widget_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType(Row $row) {
    $type = $row->getSourceProperty('type');
    $plain_text = FALSE;
    $filtered_text = FALSE;

    foreach ($row->getSourceProperty('instances') as $instance) {
      // Check if this field has plain text instances, filtered text instances,
      // or both.
      $data = unserialize($instance['data']);
      switch ($data['settings']['text_processing']) {
        case '0':
          $plain_text = TRUE;
          break;

        case '1':
          $filtered_text = TRUE;
          break;
      }
    }

    // If a text or text_long field has only plain text instances, migrate it
    // to a string or string_long field.
    $types_mappable_to_string = ['text', 'text_long'];
    if (in_array($type, $types_mappable_to_string) && $plain_text && !$filtered_text) {
      $type = str_replace(['text', 'text_long'], ['string', 'string_long'], $type);
    }

    // Types configured with both plain and formatted text instances will be
    // migrated with 'text' and 'text_long'.
    return $type;
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'value' => 'value',
        // The 'summary' property will only be picked up when the destination
        // field type is 'text_with_summary'.
        'summary' => 'summary',
        // The 'format' property is ignored for string and string_long types.
        'format' => [
          'plugin' => 'default_value',
          'source' => 'format',
          'default_value' => 'plain_text',
        ],
      ],
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
