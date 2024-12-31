<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\Constraint;

/**
 * Valid Regex constraint.
 *
 * Determines if a string is a valid regular expression.
 */
#[\Drupal\Core\Validation\Attribute\Constraint(
  id: 'ValidRegex',
  label: new TranslatableMarkup('Valid Regex', [], ['context' => 'Validation'])
)]
class ValidRegexConstraint extends Constraint {

  /**
   * The error message if validation fails.
   *
   * @var string
   */
  public string $message = "The string '@value' is not a valid regular expression.";

}
