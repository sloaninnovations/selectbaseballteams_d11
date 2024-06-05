<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Checks that all the keys of a sequence match the specified constraints.
 */
#[Constraint(
  id: 'SequenceKeys',
  label: new TranslatableMarkup('Valid sequence keys', [], ['context' => 'Validation']),
  type: ['sequence']
)]
class SequenceKeysConstraint extends SymfonyConstraint {

  /**
   * Constraint IDs + options specified that are to be applied to sequence keys.
   *
   * @var array
   */
  public array $constraints;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'constraints';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['constraints'];
  }

}
