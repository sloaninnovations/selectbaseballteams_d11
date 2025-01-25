<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

/**
 * Set this implementation to be before others.
 */
readonly class OrderBefore extends ComplexOrder {

  /**
   * Before means the priority should be larger than others.
   */
  const bool VALUE = TRUE;

}
