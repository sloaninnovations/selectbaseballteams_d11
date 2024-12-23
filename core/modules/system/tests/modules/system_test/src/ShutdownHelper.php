<?php

declare(strict_types=1);

namespace Drupal\system_test;

use PHPUnit\Framework\Exception;

/**
 * Contains shutdown dummy functions.
 */
class ShutdownHelper {

  /**
   * Dummy shutdown function which registers another shutdown function.
   */
  public function firstShutdownFunction($arg1, $arg2): void {
    // Set something to ensure that this function got called.
    \Drupal::state()->set('firstShutdownFunction', [$arg1, $arg2]);
    drupal_register_shutdown_function([$this, 'secondShutdownFunction'], $arg1, $arg2);
  }

  /**
   * Dummy shutdown function.
   */
  public function secondShutdownFunction($arg1, $arg2): void {
    // Set something to ensure that this function got called.
    \Drupal::state()->set('secondShutdownFunction', [$arg1, $arg2]);

    // Throw an exception with an HTML tag. Since this is called in a shutdown
    // function, it will not bubble up to the default exception handler but will
    // be caught in _drupal_shutdown_function() and be displayed through
    // \Drupal\Core\Utility\Error::renderExceptionSafe() if possible.
    throw new Exception('Drupal is <blink>awesome</blink>.');
  }

}
