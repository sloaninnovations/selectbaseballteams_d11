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
  label: new TranslatableMarkup('HTML 5', [], ['context' => 'Validation'])
)]
class HtmlConstraint extends SymfonyConstraint {

  /**
   * Constructs a HtmlConstraint object.
   *
   * @param mixed|null $options
   *   The options (as associative array) or the value for the default option
   *   (any other type).
   * @param string|null $mode
   *   The mode to parse the html string, either 'document' or 'fragment'.
   * @param string[]|null $groups
   *   An array of validation groups.
   * @param mixed|null $payload
   *   Domain-specific data attached to a constraint.
   */
  public function __construct(mixed $options = NULL, public ?string $mode = 'fragment', ?array $groups = NULL, mixed $payload = NULL) {
    parent::__construct($options, $groups, $payload);
    if (!in_array($this->mode, ['fragment', 'document'])) {
      throw new InvalidArgumentException('Invalid HTML parsing mode. the `mode` argument must be "fragment" or "document".');
    }
  }

}
