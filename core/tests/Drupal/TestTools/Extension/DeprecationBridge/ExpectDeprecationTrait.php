<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension\DeprecationBridge;

use Drupal\Core\Utility\Error;
use Drupal\TestTools\ErrorHandler\BootstrapErrorHandler;
use PHPUnit\Framework\Attributes\After;

/**
 * A trait to include in Drupal tests to manage expected deprecations.
 *
 * This code works in coordination with DeprecationHandler.
 *
 * This trait is a replacement for symfony/phpunit-bridge that is not
 * supporting PHPUnit 10 and above. In the future this extension might be
 * dropped if PHPUnit will support all deprecation management needs.
 *
 * @see \Drupal\TestTools\Extension\DeprecationBridge\DeprecationHandler
 *
 * @internal
 */
trait ExpectDeprecationTrait {

  /**
   * Tears down the test error handler.
   *
   * This method is run after each test's ::tearDown() method.
   */
  #[After]
  public function tearDownErrorHandler(): void {
    if (!DeprecationHandler::isEnabled()) {
      return;
    }

    // We expect that the current error handler is the one set during the
    // PHPUnit bootstrap. If not, the error handler was changed during the test
    // execution but not properly restored during ::tearDown().
    $handler = Error::currentErrorHandler();
    if (!$handler instanceof BootstrapErrorHandler) {
      throw new \RuntimeException(sprintf('%s registered its own error handler (%s) without restoring the previous one before or during tear down. This can cause unpredictable test results. Ensure the test cleans up after itself.',
        $this->name(),
        self::getCallableName($handler),
      ));
    }
  }

  /**
   * Returns a callable as a string suitable for inclusion in a message.
   *
   * @param callable $callable
   *   The callable.
   *
   * @return string
   *   The string suitable for inclusion in a message.
   *
   * @see https://stackoverflow.com/questions/34324576/print-name-or-definition-of-callable-in-php
   */
  private static function getCallableName(callable $callable): string {
    switch (TRUE) {
      case is_string($callable) && strpos($callable, '::'):
        return '[static] ' . $callable;

      case is_string($callable):
        return '[function] ' . $callable;

      case is_array($callable) && is_object($callable[0]):
        return '[method] ' . get_class($callable[0]) . '->' . $callable[1];

      case is_array($callable):
        return '[static] ' . $callable[0] . '::' . $callable[1];

      case $callable instanceof \Closure:
        return '[closure]';

      case is_object($callable):
        return '[invokable] ' . get_class($callable);

      default:
        return '[unknown]';

    }
  }

}
