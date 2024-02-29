<?php

declare(strict_types=1);

namespace Drupal\Tests\mailer\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Mailer\Transport\NullTransport;

/**
 * Tests the default transport in the child site of browser tests.
 *
 * @group mailer
 */
class TransportFactoryAdapterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'mailer',
    'mailer_transport_factory_functional_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test that the transport is set to null://null by default.
   */
  public function testDefaultTestMailFactory(): void {
    $response = $this->drupalGet('mailer-transport-factory-functional-test/transport-info');
    $actual = json_decode($response, TRUE);

    $expected = [
      'mailerDsn' => [
        'scheme' => 'null',
        'host' => 'null',
        'user' => NULL,
        'password' => NULL,
        'port' => NULL,
        'options' => [],
      ],
      'mailerTransportClass' => NullTransport::class,
    ];
    $this->assertEquals($expected, $actual);
  }

}
