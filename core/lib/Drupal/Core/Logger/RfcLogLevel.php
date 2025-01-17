<?php

namespace Drupal\Core\Logger;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

/**
 * @defgroup logging_severity_levels Logging severity levels
 * @{
 * Logging severity levels as defined in RFC 5424.
 *
 * The constant definitions of this class correspond to the logging severity
 * levels defined in RFC 5424, section 6.2.1. PHP supplies predefined LOG_*
 * constants for use in the syslog() function, but their values on Windows
 * builds do not correspond to RFC 5424. The associated PHP bug report was
 * closed with the comment, "And it's also not a bug, as Windows just have less
 * log levels," and "So the behavior you're seeing is perfectly normal."
 *
 * @see https://tools.ietf.org/html/rfc5424#section-6.2.1
 * @see http://bugs.php.net/bug.php?id=18090
 * @see http://php.net/manual/function.syslog.php
 * @see http://php.net/manual/network.constants.php
 * @see \Drupal\Core\Logger\RfcLogLevel::getLevels()
 *
 * @}
 */

/**
 * Defines various logging severity levels.
 *
 * @ingroup logging_severity_levels
 */
class RfcLogLevel {

  /**
   * Log message severity -- Emergency: system is unusable.
   */
  const EMERGENCY = 0;

  /**
   * Log message severity -- Alert: action must be taken immediately.
   */
  const ALERT = 1;

  /**
   * Log message severity -- Critical conditions.
   */
  const CRITICAL = 2;

  /**
   * Log message severity -- Error conditions.
   */
  const ERROR = 3;

  /**
   * Log message severity -- Warning conditions.
   */
  const WARNING = 4;

  /**
   * Log message severity -- Normal but significant conditions.
   */
  const NOTICE = 5;

  /**
   * Log message severity -- Informational messages.
   */
  const INFO = 6;

  /**
   * Log message severity -- Debug-level messages.
   */
  const DEBUG = 7;

  /**
   * An array with the severity levels as keys and labels as values.
   *
   * @var array
   */
  protected static $levels;

  /**
   * Returns a list of severity levels, as defined in RFC 5424.
   *
   * @return array
   *   Array of the possible severity levels for log messages.
   *
   * @see http://tools.ietf.org/html/rfc5424
   * @ingroup logging_severity_levels
   */
  public static function getLevels() {
    if (!static::$levels) {
      static::$levels = [
        static::EMERGENCY => new TranslatableMarkup('Emergency'),
        static::ALERT => new TranslatableMarkup('Alert'),
        static::CRITICAL => new TranslatableMarkup('Critical'),
        static::ERROR => new TranslatableMarkup('Error'),
        static::WARNING => new TranslatableMarkup('Warning'),
        static::NOTICE => new TranslatableMarkup('Notice'),
        static::INFO => new TranslatableMarkup('Info'),
        static::DEBUG => new TranslatableMarkup('Debug'),
      ];
    }

    return static::$levels;
  }

  /**
   * Returns the RFC 5424 severity level for a given PSR-3 log level.
   *
   * @param string $level
   *   A PSR-3 log level.
   *
   * @return int
   *   The RFC 5424 severity level.
   *
   * @throws \Psr\Log\InvalidArgumentException
   *   If the log level is not recognized.
   */
  public static function fromPsr3(string $level): int {
    return match ($level) {
      LogLevel::EMERGENCY => static::EMERGENCY,
      LogLevel::ALERT => static::ALERT,
      LogLevel::CRITICAL => static::CRITICAL,
      LogLevel::ERROR => static::ERROR,
      LogLevel::WARNING => static::WARNING,
      LogLevel::NOTICE => static::NOTICE,
      LogLevel::INFO => static::INFO,
      LogLevel::DEBUG => static::DEBUG,
      default => throw new InvalidArgumentException("Invalid log level: $level"),
    };

  }

  /**
   * Returns the PSR-3 log level for a given RFC 5424 severity level.
   *
   * @param int $level
   *   The RFC 5424 severity level.
   *
   * @return string
   *   A PSR-3 log level.
   *
   * @throws \Psr\Log\InvalidArgumentException
   *   If the severity level is not recognized.
   */
  public static function toPsr3(int $level): string {
    return match ($level) {
      static::EMERGENCY => LogLevel::EMERGENCY,
      static::ALERT => LogLevel::ALERT,
      static::CRITICAL => LogLevel::CRITICAL,
      static::ERROR => LogLevel::ERROR,
      static::WARNING => LogLevel::WARNING,
      static::NOTICE => LogLevel::NOTICE,
      static::INFO => LogLevel::INFO,
      static::DEBUG => LogLevel::DEBUG,
      default => throw new InvalidArgumentException("Invalid log level: $level"),
    };
  }

}
