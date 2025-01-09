<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * IsCommentable constraint.
 */
#[Constraint(
  id: 'IsCommentable',
  label: new TranslatableMarkup('IsCommentable', [], ['context' => 'Validation']),
  type: FALSE
)]
class IsCommentableConstraint extends SymfonyConstraint {

  /**
   * The error message if validation fails.
   *
   * @var string
   */
  public $message = "The '@entity_type_id' entity is not commentable";

}
