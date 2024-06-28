<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * EntityTypeExists constraint.
 */
#[Constraint(
  id: 'EntityTypeExists',
  label: new TranslatableMarkup(
    'EntityTypeExists',
    [],
    ['context' => 'Validation']
  ),
  type: FALSE
)]
class EntityTypeExistsConstraint extends SymfonyConstraint {

  /**
   * The error message if validation fails.
   *
   * @var string
   */
  public $message = "The '@entity_type_id' entity type does not exist.";

}
