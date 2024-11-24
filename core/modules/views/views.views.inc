<?php

/**
 * @file
 */

use Drupal\field\FieldConfigInterface;

/**
 * Returns the label of a certain field.
 *
 * Therefore it looks up in all bundles to find the most used field.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
 * \Drupal::service('entity_field.manager')
 * ->getFieldLabels($entity_type, $field_name); instead.
 * @see https://www.drupal.org/node/3489411
 */
function views_entity_field_label($entity_type, $field_name) {
  @trigger_error('views_entity_field_label() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use \Drupal::service(\'entity_field.manager\')->getFieldLabels($entity_type, $field_name). See https://www.drupal.org/node/2317841', E_USER_DEPRECATED);
  return \Drupal::service('entity_field.manager')->getFieldLabels($entity_type, $field_name);
}
