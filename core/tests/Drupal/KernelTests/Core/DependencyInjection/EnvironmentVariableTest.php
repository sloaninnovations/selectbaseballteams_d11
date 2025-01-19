<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\DependencyInjection;

use Drupal\container_env_test\TestService;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests integration of the container with environment variables.
 */
class EnvironmentVariableTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['container_env_test'];

  protected function setUp(): void {
    $_ENV['CONTAINER_ENV_TEST'] = 'test-variable';
    $_ENV['CONTAINER_ENV_TEST_WITH_DEFAULT2'] = 'override-default';
    parent::setUp();
  }

  public function testEnvironmentVariable(): void {
    $this->assertEquals('test-variable', $this->container->getParameter('container_env_test'));
    $this->assertEquals('some-test', $this->container->getParameter('container_env_test_with_default'));
    $this->assertEquals('override-default', $this->container->getParameter('container_env_test_with_default2'));

    $service = \Drupal::service(TestService::class);
    assert($service instanceof TestService);
    $this->assertEquals('test-variable', $service->parameter);
  }

}
