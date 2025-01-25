<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

/**
 * Set this implementation to be after others.
 */
readonly class OrderAfter extends ComplexOrder {

  /**
   * After means the priority should not be larger than others.
   */
  const bool VALUE = FALSE;

}
