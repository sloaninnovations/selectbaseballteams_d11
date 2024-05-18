<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Valid Regex constraint.
 *
 * Determines if a string is a valid regular expression.
 */
#[Constraint(
  id: 'ValidRegex',
  label: new TranslatableMarkup('Valid Regex', [], ['context' => 'Validation'])
)]
class ValidRegexConstraint extends SymfonyConstraint {

  /**
   * The error message if validation fails.
   *
   * @var string
   */
  public string $message = 'The value "@regex" is not valid: @message.';

}
