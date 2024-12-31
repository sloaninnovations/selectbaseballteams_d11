<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidRegexConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!$constraint instanceof ValidRegexConstraint) {
      throw new UnexpectedTypeException($constraint, ValidRegexConstraint::class);
    }
    if (NULL === $value || '' === $value) {
      return;
    }

    if (!\is_scalar($value) && !$value instanceof \Stringable) {
      throw new UnexpectedValueException($value, 'string');
    }

    $value = (string) $value;
    set_error_handler(function () {}, E_WARNING);
    $valid_regex = preg_match($value, "") !== FALSE;
    restore_error_handler();
    if (!$valid_regex || preg_last_error() !== PREG_NO_ERROR) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('@value', $value)
        ->addViolation();
    }
  }

}
