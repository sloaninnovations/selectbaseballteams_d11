<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Attribute to request this hook implementation to fire first.
 *
 * @section sec_backwards_compatibility Backwards-compatibility
 *
 * @see HookOrderGroup
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class HookFirst extends HookOrderBase {

  /**
   * Constructs a HookFirst attribute.
   */
  public function __construct() {
    parent::__construct(TRUE);
  }

}
