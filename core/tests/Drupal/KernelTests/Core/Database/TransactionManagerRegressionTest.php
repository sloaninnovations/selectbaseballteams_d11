<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the regression of issue #3440848.
 *
 * @group Database
 */
class TransactionManagerRegressionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
  ];

  /**
   * Tests a request which passes after issue #3440848.
   */
  public function testRequest(): void {
    $request = Request::create('/test');
    $response = $this->container->get('http_kernel')->handle($request);
    $this->assertEquals(404, $response->getStatusCode());
  }

  /**
   * Tests a request which may fail after issue #3440848.
   *
   * Fails with MySQL database driver.
   */
  public function testRequestFail(): void {
    $this->installEntitySchema('user');
    $request = Request::create('/test');
    $response = $this->container->get('http_kernel')->handle($request);
    $this->assertEquals(404, $response->getStatusCode());
  }

}
