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
   *   The modules this implementation should run before.
   */
  public function __construct(
    public array $modules,
  ) {
    parent::__construct(FALSE);
  }

}
