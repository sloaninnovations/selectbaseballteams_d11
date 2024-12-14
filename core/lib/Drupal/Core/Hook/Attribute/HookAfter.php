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
   * @param array $modules
   *   A list of things this implementation should run after. Each thing is
   *   either a module name or a list of class and method.
   */
  public function __construct(
    public readonly array $modules,
  ) {
    parent::__construct(FALSE);
  }

}
