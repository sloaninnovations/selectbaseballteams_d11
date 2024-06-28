<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates complex data.
 */
class EntityTypeExistsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    assert($constraint instanceof EntityTypeExistsConstraint);

    if (!is_string($value)) {
      throw new UnexpectedTypeException($value, 'string');
    }

    $entity_types = \Drupal::entityTypeManager()->getDefinitions();

    if (!array_key_exists($value, $entity_types)) {
      $this->context->addViolation($constraint->message, [
        '@entity_type_id' => $value,
      ]);
    }
  }
}
