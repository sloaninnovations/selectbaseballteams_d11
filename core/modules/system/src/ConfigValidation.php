<?php

declare(strict_types=1);

namespace Drupal\system;

/**
 * Provides validation methods for configuration values.
 */
class ConfigValidation {

  /**
   * Validates whether a given scheme is valid.
   *
   * @param string $scheme
   *   The file scheme to validate.
   *
   * @return bool
   *   TRUE if the scheme is valid, FALSE otherwise.
   */
  public static function isValidScheme(string $scheme): bool {
    return \Drupal::service('stream_wrapper_manager')->isValidScheme($scheme);
  }

}
