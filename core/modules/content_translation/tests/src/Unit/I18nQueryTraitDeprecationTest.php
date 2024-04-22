<?php

declare(strict_types=1);

namespace Drupal\Tests\content_translation\Unit;

use Drupal\content_translation\Plugin\migrate\source\I18nQueryTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Deprecation test for I18nQueryTrait.
 *
 * @group migrate
 */
class I18nQueryTraitDeprecationTest extends UnitTestCase {

  /**
   * Test that classes using I18nQueryTrait trigger deprecations.
   */
  public function testDeprecation(): void {
    $previous_error_handler = set_error_handler(function ($severity, $message, $file, $line) use (&$previous_error_handler) {
      // Convert deprecation error into a catchable exception.
      if ($severity === E_USER_DEPRECATED) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
      }
      if ($previous_error_handler) {
        return $previous_error_handler($severity, $message, $file, $line);
      }
    });

    try {
      // @phpstan-ignore-next-line
      $this->getMockForTrait(I18nQueryTrait::class);
      $this->fail('No deprecation error triggered.');
    }
    catch (\ErrorException $e) {
      $this->assertSame('\Drupal\content_translation\Plugin\migrate\source\I18nQueryTrait is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Instead, use \Drupal\migrate_drupal\Plugin\migrate\source\I18nQueryTrait. See https://www.drupal.org/node/3439256', $e->getMessage());
    }

    restore_error_handler();
  }

}
