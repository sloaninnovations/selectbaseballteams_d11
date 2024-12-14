<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Attribute to request this hook implementation to fire after others.
 *
 * @section sec_backwards_compatibility Backwards-compatibility
 *
 * @see HookOrderGroup
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class HookAfter extends HookOrderBase {

  /**
   * Constructs a HookAfter attribute.
   *
   * @param array $orderings
   *   Each ordering is either a module name or a class and method pair array.
   */
  public function __construct(
    public readonly array $orderings,
  ) {
    parent::__construct(FALSE);
  }

}
