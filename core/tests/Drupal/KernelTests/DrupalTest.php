<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * @coversDefaultClass \Drupal
 *
 * @group PHPUnit
 * @group Test
 * @group KernelTests
 */
class DrupalTest extends KernelTestBase {

  /**
   * @covers ::serviceByClass
   */
  public function testServiceByClass(): void {
    $event_dispatcher = \Drupal::serviceByClass(EventDispatcherInterface::class);
    $this->assertInstanceOf(EventDispatcherInterface::class, $event_dispatcher);

    $php_logger = \Drupal::serviceByClass(LoggerInterface::class, 'logger.channel.php');
    $this->assertInstanceOf(LoggerChannel::class, $php_logger);
    $this->assertSame(\Drupal::service('logger.channel.php'), $php_logger);
    $this->assertNotSame(\Drupal::service('logger.channel.menu'), $php_logger);

    $this->callAndAssertException(
      ServiceNotFoundException::class,
      static function () {
        // Request a service with the wrong class.
        \Drupal::serviceByClass(EntityTypeManagerInterface::class, 'logger.channel.php');
      },
    );

    $this->callAndAssertException(
      ServiceNotFoundException::class,
      static function () {
        // Request a service that surely does not exist.
        \Drupal::serviceByClass(get_class(new class () {}));
      },
    );
  }

  /**
   * Calls a callback, and asserts that an exception is thrown.
   *
   * @param class-string $exception_class
   *   Expected exception class.
   * @param callable(): (void|mixed) $callback
   *   A callback to call.
   */
  protected function callAndAssertException(string $exception_class, callable $callback): void {
    try {
      $callback();
    }
    catch (\Throwable $e) {
      $this->assertSame($exception_class, get_class($e));
      return;
    }
    $this->fail("No exception was thrown.");
  }

}
