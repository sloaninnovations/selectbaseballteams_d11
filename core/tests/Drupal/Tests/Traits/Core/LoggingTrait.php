<?php

declare(strict_types=1);

namespace Drupal\Tests\Traits\Core;

use Drupal\KernelTests\AssertableLogger;

/**
 * Sets test expectations for generated log messages.
 *
 * A test class using this trait should:
 * - ensure that AssertableLogger::log() is called when logs are generated,
 * - provide a getAssertableLogger() method that returns that logger, and
 * - call LoggingTrait::assertLogExpectationsMet(), typically in its
 * assertPostConditions() method.
 *
 * In order to assert that a test does or does not generate logs, the test
 * must call LoggingTrait::expectLog() or
 * LoggingTrait::expectNoLogsAsSevereAs(); it may also call
 * LoggingTrait::allowLogsAsSevereAs().
 */
trait LoggingTrait {

  /**
   * Setup an expectation that a test will generate a log message.
   *
   * If a matching log message is not generated, the test will fail.
   * @code
   * // Fail a test if it does not generate a warning on the
   * // 'some_module' channel that contains the text 'invalid' in its
   * // message.
   * $this->expectLog(RfcLogLevel::WARNING, 'some_module', 'invalid');
   * @endcode
   *
   * @param int $level
   *   The log level as defined in Drupal\Core\Logger\RfcLogLevel.
   * @param string $channel
   *   The logger channel.
   * @param string $message
   *   (optional) Text that the log message must contain.
   */
  protected function expectLog(int $level, string $channel, string $message = ''): void {
    $this->getAssertableLogger()->expectLog($level, $channel, $message);
  }

  /**
   * Setup an expectation that a test will not generate a log message.
   *
   * If a matching log message is generated, the test will fail. Log
   * messages of the specified level or more severe will trigger a test
   * to fail as soon as they are received.
   *
   * Log messages that are set up as expected (by ::expectLog()) or
   * are set up as allowed (by ::allowLogsAsSevereAs()) are exempt and
   * will not trigger failure.
   *
   * @code
   * // Fail a test if it generates any warnings or errors.
   * $this->expectNoLogsAsSevereAs(RfcLogLevel::WARNING);
   * @endcode
   *
   * @param int $level
   *   A log level as defined in Drupal\Core\Logger\RfcLogLevel.
   * @param string $channel
   *   (optional) A logger channel.
   * @param string $message
   *   (optional) Text that the log message must contain.
   */
  protected function expectNoLogsAsSevereAs(int $level, string $channel = '', string $message = ''): void {
    $this->getAssertableLogger()->expectNoLogsAsSevereAs($level, $channel, $message);
  }

  /**
   * Define a certain kind of log message as allowed.
   *
   * If a generated log message matches the specified parameters, then
   * it will not cause a test to fail even if would otherwise have been
   * disallowed (by ::expectNoLogsAsSevereAs()).
   *
   * Typically ::expectNoLogsAsSevereAs() is used to define a broad class of
   * unacceptable log messages, e.g. 'fail all warnings and above', and
   * then allowLogsAsSevereAs() is used to define an narrower exception to that,
   * e.g. 'except allow warnings from the user channel'. The order in which
   * these methods are called does not matter.
   *
   * @code
   * // Fail a test if it generates any warnings or errors, except for
   * // warnings on the 'some_module' channel.
   * $this->expectNoLogsAsSevereAs(RfcLogLevel::WARNING);
   * $this->allowLogsAsSevereAs(RfcLogLevel::WARNING, 'some_module');
   * @endcode
   *
   * @param int $level
   *   The log level as defined in Drupal\Core\Logger\RfcLogLevel.
   * @param string $channel
   *   The logger channel.
   * @param string $message
   *   (optional) Text that the log message must contain.
   */
  protected function allowLogsAsSevereAs(int $level, string $channel, string $message = ''): void {
    $this->getAssertableLogger()->allowLogsAsSevereAs($level, $channel, $message);
  }

  /**
   * Assert that no logs were expected that have not been received.
   */
  protected function assertLogExpectationsMet(): void {
    $logger = $this->getAssertableLogger();

    $disallowed_logs = $logger->getDisallowedLogs();
    if (!empty($disallowed_logs)) {
      $this->fail("Logs were generated during the test that were explicitly expected not to be generated. " . print_r($disallowed_logs, TRUE));
    }

    if ($logger->hasExpectations()) {
      $unmet_expectations = $logger->getUnmetExpectations();
      $this->assertEmpty($unmet_expectations, "Logs were expected to be generated during the test, but were not. " . print_r($unmet_expectations, TRUE));
    }

    $logger->reset();
  }

  /**
   * Get or create an assertable logger.
   *
   * @return \Drupal\KernelTests\AssertableLogger
   *   An assertable logger.
   */
  abstract protected function getAssertableLogger(): AssertableLogger;

}
