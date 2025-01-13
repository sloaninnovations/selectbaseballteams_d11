<?php

declare(strict_types=1);

namespace Drupal\entity_test;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Simple object with callbacks.
 */
class Callbacks {

  /**
   * Validation handler for the entity_test entity form.
   */
  public static function entityTestFormValidate(array &$form, FormStateInterface $form_state): void {
    $form['#entity_test_form_validate'] = TRUE;
  }

  /**
   * Validation handler for the entity_test entity form.
   */
  public static function entityTestFormValidateCheck(array &$form, FormStateInterface $form_state): void {
    if (!empty($form['#entity_test_form_validate'])) {
      \Drupal::state()->set('entity_test.form.validate.result', TRUE);
    }
  }

  /**
   * Field default value callback.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity being created.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition.
   *
   * @return array
   *   An array of default values, in the same format as the $default_value
   *   property.
   *
   * @see \Drupal\field\Entity\FieldConfig::$default_value
   */
  public static function entityTestFieldDefaultValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition): array {
    // Include the field name and entity language in the generated values to
    // check that they are correctly passed.
    $string = $definition->getName() . '_' . $entity->language()->getId();
    // Return a "default value" with multiple items.
    return [
      [
        'shape' => "shape:0:$string",
        'color' => "color:0:$string",
      ],
      [
        'shape' => "shape:1:$string",
        'color' => "color:1:$string",
      ],
    ];
  }

}
