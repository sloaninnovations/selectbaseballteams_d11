<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\Validation\TypedDataAwareValidatorTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the NotBlankAfterInstall constraint.
 */
class NotBlankAfterInstallConstraintValidator extends ConstraintValidator {

  use TypedDataAwareValidatorTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    assert($constraint instanceof NotBlankAfterInstallConstraint);

    if (!empty($value)) {
      return;
    }
    // If un-wrapped data has been passed, fetch the typed data object first.
    if (!$value instanceof TypedDataInterface) {
      $value = $this->getTypedData();
    }
    if (InstallerKernel::installationAttempted()) {
      // @todo Decide how to bypass NotNullConstraint.
    }
    else {
      $this->context->buildViolation($constraint->message)
        ->setParameter('%field', $this->context->getPropertyPath())
        ->addViolation();
    }
  }

}
