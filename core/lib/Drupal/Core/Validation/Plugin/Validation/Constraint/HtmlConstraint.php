<?php

declare(strict_types=1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Drupal\Core\Validation\Attribute\Constraint;

/**
 * Valid HTML constraint.
 *
 * Determines if a string is valid HTML5.
 */
#[Constraint(
  id: 'Html5',
  label: new TranslatableMarkup('Valid HTML', [], ['context' => 'Validation'])
)]
class HtmlConstraint extends SymfonyConstraint {

  public function __construct(mixed $options = NULL, public string $mode = 'fragment', ?array $groups = NULL, mixed $payload = NULL) {
    parent::__construct($options, $groups, $payload);
    if (!in_array($this->mode, ['fragment', 'document'])) {
      throw new InvalidArgumentException('Invalid HTML parsing mode. the `mode` argument must be "fragment" or "document".');
    }
  }

}
