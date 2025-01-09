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
  label: new TranslatableMarkup('Entity type exists', [], ['context' => 'Validation']),
  type: FALSE
)]
class EntityTypeExistsConstraint extends SymfonyConstraint {

  public string $requiredInterface;

  /**
   * The error message if validation fails.
   *
   * @var string
   */
  public $message = "The '@entity_type_id' entity type does not exist.";

  /**
   * The error message if validation of interface fails.
   *
   * @var string
   */
  public $interfaceMissingMessage = 'The @entity_type_id entity type does not implement the @interface interface.';

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'requiredInterface';
  }

}
