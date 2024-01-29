<?php

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\PrimitiveBase;
use Drupal\Core\TypedData\Type\IntegerInterface;

/**
 * The integer data type.
 *
 * The plain value of an integer is a regular PHP integer. For setting the value
 * any PHP variable that casts to an integer may be passed.
 *
 * @DataType(
 *   id = "integer",
 *   label = @Translation("Integer")
 * )
 */
class IntegerData extends PrimitiveBase implements IntegerInterface {

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    if ($this->value === NULL) {
      return NULL;
    }

    // Special handling for integers since the configuration system is primarily
    // concerned with saving values from the Form API we have to special
    // the meaning of an empty string for numeric types. In PHP this would be
    // casted to a 0 but for the purposes of configuration we need to treat this
    // as a NULL.
    if ($this->value === '') {
      return NULL;
    }

    return (int) $this->value;
  }

}
