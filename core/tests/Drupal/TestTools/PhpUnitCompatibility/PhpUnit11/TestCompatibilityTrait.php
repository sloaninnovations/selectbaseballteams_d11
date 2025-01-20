<?php

declare(strict_types=1);

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit11;

/**
 * Drupal's forward compatibility layer with multiple versions of PHPUnit.
 *
 * @internal
 */
trait TestCompatibilityTrait {

  /**
   * Adds an expected deprecation.
   *
   * @param string $message
   *   The expected deprecation message.
   */
  public function expectDeprecation(string $message): void {
    $this->expectUserDeprecationMessageMatches($this->regularExpressionForFormatDescription('%A' . $message . '%A'));
  }

  private function regularExpressionForFormatDescription(string $string): string {
    $string = strtr(preg_quote($string, '/'), [
      '%%' => '%',
      '%e' => preg_quote(\DIRECTORY_SEPARATOR, '/'),
      '%s' => '[^\r\n]+',
      '%S' => '[^\r\n]*',
      '%a' => '.+?',
      '%A' => '.*?',
      '%w' => '\s*',
      '%i' => '[+-]?\d+',
      '%d' => '\d+',
      '%x' => '[0-9a-fA-F]+',
      '%f' => '[+-]?(?:\d+|(?=\.\d))(?:\.\d+)?(?:[Ee][+-]?\d+)?',
      '%c' => '.',
      '%0' => '\x00',
    ]);
    return '/^' . $string . '$/s';
  }

}
