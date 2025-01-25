<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

/**
 * Set this implementation to be first or last.
 */
enum Order: int {

  /**
   * This implementation should fire first.
   */
  case First = 1;

  /**
   * This implementation should fire last.
   */
  case Last = 0;

}
