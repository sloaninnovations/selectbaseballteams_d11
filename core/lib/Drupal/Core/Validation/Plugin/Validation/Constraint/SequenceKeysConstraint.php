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

  /**
   * Returns the list of valid keys.
   *
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
   *   The current execution context.
   *
   * @return string[]
   *   The keys that will be considered valid.
   */
  public function getAllowedKeys(ExecutionContextInterface $context): array {
    $mapping = $context->getObject();
    assert($mapping instanceof Mapping);
    $resolved_type = $mapping->getDataDefinition()->getDataType();
    $valid_keys = $mapping->getValidKeys();

    // If we were given an explicit array of allowed keys, return that.
    if (is_array($this->allowedKeys)) {
      if (!empty(array_diff($this->allowedKeys, $valid_keys))) {
        throw new InvalidArgumentException(sprintf(
          'The type \'%s\' explicitly specifies the allowed keys (%s), but they are not a subset of the statically defined mapping keys in the schema (%s).',
          $resolved_type,
          implode(', ', $this->allowedKeys),
          implode(', ', $valid_keys)
        ));
      }
      return array_intersect($valid_keys, $this->allowedKeys);
    }
    // The only other value we'll accept is the string `<infer>`.
    elseif ($this->allowedKeys === '<infer>') {
      return $mapping->getValidKeys();
    }
    throw new InvalidArgumentException("'$this->allowedKeys' is not a valid set of allowed keys.");
  }

}
