<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Logger;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\KernelTests\AssertableLogger;
use Drupal\Tests\Traits\Core\LoggingTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\AssertionFailedError;

/**
 * @coversDefaultClass \Drupal\Tests\Traits\Core\LoggingTrait
 * @group PHPUnit
 */
class LoggingTraitTest extends UnitTestCase {

  use LoggingTrait;

  /**
   * @var \Drupal\KernelTests\AssertableLogger
   */
  protected AssertableLogger $assertableLogger;

  /**
   * {@inheritdoc}
   */
  protected function getAssertableLogger(): AssertableLogger {
    if (!isset($this->assertableLogger)) {
      $this->assertableLogger = new AssertableLogger();
    }
    return $this->assertableLogger;
  }

  /**
   * @dataProvider expectLogMetProvider
   */
  public function testExpectLogMet(array $expectation): void {
    $this->expectLog(...$expectation);
    $this->emitLog(RfcLogLevel::WARNING, 'channel_a', 'some message');
    $this->assertLogExpectationsMet();
  }

  public static function expectLogMetProvider(): array {
    return [
      [[RfcLogLevel::WARNING, 'channel_a']],
      [[RfcLogLevel::WARNING, 'channel_a', 'some message']],
      [[RfcLogLevel::WARNING, 'channel_a', 'message']],
      [[RfcLogLevel::WARNING, 'channel_a', 'some']],
    ];
  }

  /**
   * @dataProvider expectLogUnmetProvider
   */
  public function testExpectLogUnmet(array $expectation): void {
    $this->expectLog(...$expectation);
    $this->emitLog(RfcLogLevel::WARNING, 'channel_a', 'some message');
    $this->assertNotEmpty($this->getAssertableLogger()->getUnmetExpectations());
  }

  public static function expectLogUnmetProvider(): array {
    return [
      [[RfcLogLevel::WARNING, 'channel_b']],
      [[RfcLogLevel::WARNING, 'channel_a', 'some other message']],
      [[RfcLogLevel::ERROR, 'channel_a']],
      [[RfcLogLevel::ERROR, 'channel_a', 'some message']],
      [[RfcLogLevel::ERROR, 'channel_b']],
      [[RfcLogLevel::ERROR, 'channel_b', 'some message']],
      [[RfcLogLevel::NOTICE, 'channel_a']],
      [[RfcLogLevel::NOTICE, 'channel_a', 'some message']],
      [[RfcLogLevel::NOTICE, 'channel_b']],
      [[RfcLogLevel::NOTICE, 'channel_b', 'some message']],
    ];
  }

  /**
   * @dataProvider expectNoLogMetProvider
   */
  public function testExpectNoLogMet(array $expectation): void {
    $this->expectNoLogsAsSevereAs(...$expectation);
    $this->emitLog(RfcLogLevel::WARNING, 'channel_a', 'some message');
    $this->expectNotToPerformAssertions();
  }

  public static function expectNoLogMetProvider(): array {
    return [
      [[RfcLogLevel::NOTICE, 'channel_a', 'some other message']],
      [[RfcLogLevel::NOTICE, 'channel_b']],
      [[RfcLogLevel::NOTICE, 'channel_b', 'some']],
      [[RfcLogLevel::NOTICE, 'channel_b', 'message']],
      [[RfcLogLevel::NOTICE, 'channel_b', 'some message']],
      [[RfcLogLevel::NOTICE, 'channel_b', 'some other message']],
      [[RfcLogLevel::WARNING, 'channel_a', 'some other message']],
      [[RfcLogLevel::WARNING, 'channel_b']],
      [[RfcLogLevel::WARNING, 'channel_b', 'some']],
      [[RfcLogLevel::WARNING, 'channel_b', 'message']],
      [[RfcLogLevel::WARNING, 'channel_b', 'some message']],
      [[RfcLogLevel::WARNING, 'channel_b', 'some other message']],
      [[RfcLogLevel::ERROR, 'channel_a', 'some other message']],
      [[RfcLogLevel::ERROR, 'channel_b']],
      [[RfcLogLevel::ERROR, 'channel_b', 'some']],
      [[RfcLogLevel::ERROR, 'channel_b', 'message']],
      [[RfcLogLevel::ERROR, 'channel_b', 'some message']],
      [[RfcLogLevel::ERROR, 'channel_b', 'some other message']],
    ];
  }

  /**
   * @dataProvider expectNoLogUnmetProvider
   */
  public function testExpectNoLogUnmet(array $expectation): void {
    $this->expectNoLogsAsSevereAs(...$expectation);
    // These calls to allowLogsAsSevereAs() should have no consequences
    // because the actual log will use a different channel.
    $this->allowLogsAsSevereAs(RfcLogLevel::WARNING, 'channel_b');
    $this->allowLogsAsSevereAs(RfcLogLevel::WARNING, 'channel_b', 'some message');
    $this->emitLog(RfcLogLevel::WARNING, 'channel_a', 'some message');
    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessageMatches('/^Logs were generated during the test that were explicitly expected not to be generated/');
    $this->assertLogExpectationsMet();
  }

  public static function expectNoLogUnmetProvider(): array {
    return [
      [[RfcLogLevel::NOTICE]],
      [[RfcLogLevel::NOTICE, 'channel_a']],
      [[RfcLogLevel::NOTICE, 'channel_a', 'some']],
      [[RfcLogLevel::NOTICE, 'channel_a', 'message']],
      [[RfcLogLevel::NOTICE, 'channel_a', 'some message']],
      [[RfcLogLevel::WARNING]],
      [[RfcLogLevel::WARNING, 'channel_a']],
      [[RfcLogLevel::WARNING, 'channel_a', 'some']],
      [[RfcLogLevel::WARNING, 'channel_a', 'message']],
      [[RfcLogLevel::WARNING, 'channel_a', 'some message']],
    ];
  }

  /**
   * @dataProvider allowLogProvider
   */
  public function testAllowLogLevel(array $expectation): void {
    $this->allowLogsAsSevereAs(...$expectation);
    $this->expectNoLogsAsSevereAs(RfcLogLevel::WARNING);
    $this->emitLog(RfcLogLevel::WARNING, 'channel_a', 'some message');
    $this->expectNotToPerformAssertions();
  }

  /**
   * @dataProvider allowLogProvider
   */
  public function testAllowLogLevelChannel(array $expectation): void {
    $this->allowLogsAsSevereAs(...$expectation);
    $this->expectNoLogsAsSevereAs(RfcLogLevel::WARNING, 'channel_a');
    $this->emitLog(RfcLogLevel::WARNING, 'channel_a', 'some message');
    $this->expectNotToPerformAssertions();
  }

  public static function allowLogProvider(): array {
    return [
      [[RfcLogLevel::WARNING, 'channel_a']],
      [[RfcLogLevel::WARNING, 'channel_a', 'some']],
      [[RfcLogLevel::WARNING, 'channel_a', 'message']],
      [[RfcLogLevel::WARNING, 'channel_a', 'some message']],
      [[RfcLogLevel::ERROR, 'channel_a']],
      [[RfcLogLevel::ERROR, 'channel_a', 'some']],
      [[RfcLogLevel::ERROR, 'channel_a', 'message']],
      [[RfcLogLevel::ERROR, 'channel_a', 'some message']],
    ];
  }

  /**
   * @dataProvider expectLogMetProvider
   */
  public function testExpectLogBeforeExpectNoLog(array $expectation): void {
    $this->expectLog(...$expectation);
    $this->expectNoLogsAsSevereAs(RfcLogLevel::WARNING);
    $this->emitLog(RfcLogLevel::WARNING, 'channel_a', 'some message');
    $this->expectNotToPerformAssertions();
  }

  /**
   * Checks that tests with no logs do not perform assertions.
   *
   * @doesNotPerformAssertions
   *
   * @covers ::assertLogExpectationsMet
   */
  public function testDoesNotPerformAssertions(): void {
    $this->assertLogExpectationsMet();
  }

  protected function emitLog(int $level, string $channel, string $message): void {
    $this->getAssertableLogger()->log($level, $message, ['channel' => $channel]);
  }

}
