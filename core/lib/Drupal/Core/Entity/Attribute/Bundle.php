<?php

declare(strict_types=1);

namespace Drupal\Core\Entity\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The bundle attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Bundle extends Plugin {

  /**
   * Constructs a Bundle attribute.
   *
   * @param string $entityType
   *   The entity type ID.
   * @param string|null $bundle
   *   (optional) The bundle ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The bundle label.
   */
  public function __construct(
    public readonly string $entityType,
    public ?string $bundle = NULL,
    public readonly ?TranslatableMarkup $label = NULL,
  ) {
    $this->bundle ??= $this->entityType;
    parent::__construct($this->entityType . ':' . $this->bundle);
  }

}
