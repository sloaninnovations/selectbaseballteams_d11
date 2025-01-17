<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\EventSubscriber;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Psr\Log\LogLevel;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Tests that HTTP exceptions are logged correctly.
 *
 * @group system
 */
class ExceptionLoggingSubscriberTest extends KernelTestBase {

  /**
   * The service name for a logger implementation that collects anything logged.
   *
   * @var string
   */
  protected $testLogServiceName = 'exception_logging_subscriber_test.logger';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'test_page_test'];

  /**
   * Tests \Drupal\Core\EventSubscriber\ExceptionLoggingSubscriber::onException().
   *
   * @dataProvider exceptionDataProvider
   */
  public function testExceptionLogging(int $error_code, string $channel, string $log_level, string $exception = ''): void {
    $http_kernel = \Drupal::service('http_kernel');

    // Ensure that noting is logged.
    $this->assertEmpty($this->container->get($this->testLogServiceName)->cleanLogs());

    // Temporarily disable error log as the ExceptionLoggingSubscriber logs 5xx
    // HTTP errors using error_log().
    $error_log = ini_set('error_log', '/dev/null');
    $request = Request::create('/test-http-response-exception/' . $error_code);

    if ($exception) {
      $this->expectException($exception);
    }
    $http_kernel->handle($request);
    ini_set('error_log', $error_log);

    $logs = $this->container->get($this->testLogServiceName)->cleanLogs();
    $this->assertEquals($channel, $logs[0][2]['channel']);
    $this->assertEquals($log_level, $logs[0][0]);

    // Verify that @backtrace_string is removed from client error.
    if ($logs[0][2]['channel'] === 'client error') {
      $this->assertArrayNotHasKey('@backtrace_string', $logs[0][2]);
    }
  }

  public static function exceptionDataProvider(): array {
    return [
      // When a BadRequestException is thrown, DefaultHttpExceptionSubscriber
      // will rethrow the exception.
      [400, 'client error', LogLevel::WARNING, HttpException::class],
      [401, 'client error', LogLevel::WARNING],
      [403, 'access denied', LogLevel::WARNING],
      [404, 'page not found', LogLevel::WARNING],
      [405, 'client error', LogLevel::WARNING],
      [408, 'client error', LogLevel::WARNING],
      // Do not check the 500 status code here because it would be caught by
      // Drupal\Core\EventSubscriberExceptionTestSiteSubscriber which has lower
      // priority.
      [501, 'php', LogLevel::ERROR],
      [502, 'php', LogLevel::ERROR],
      [503, 'php', LogLevel::ERROR],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container
      ->register($this->testLogServiceName, BufferingLogger::class)
      ->addTag('logger');
  }

}
