<?php

namespace Drupal\system;

/**
 * Provides a collection of validation callbacks for testing purposes.
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
