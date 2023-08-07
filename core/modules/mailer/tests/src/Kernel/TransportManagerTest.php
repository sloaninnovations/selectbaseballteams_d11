<?php

namespace Drupal\Tests\mailer\Kernel;

use Drupal\mailer\TransportManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\mailer_transport_manager_test\Mailer\Transport\CanaryTransport;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

/**
 * Tests the transport manager.
 *
 * @group mailer
 * @coversDefaultClass \Drupal\mailer\TransportManager
 */
class TransportManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['mailer', 'system'];

  /**
   * @covers ::getTransport
   */
  public function testDefaultTestMailFactory(): void {
    $manager = $this->container->get('mailer.transport_manager');
    assert($manager instanceof TransportManagerInterface);

    $actual = $manager->getTransport();
    $this->assertInstanceOf(NullTransport::class, $actual);
  }

  /**
   * @dataProvider providerTestBuiltinFactory
   * @covers ::getTransport
   */
  public function testBuiltinFactory(string $dsn, string $expected): void {
    $manager = $this->container->get('mailer.transport_manager');
    assert($manager instanceof TransportManagerInterface);

    $actual = $manager->getTransport($dsn);
    $this->assertInstanceOf($expected, $actual);
  }

  /**
   * Provides test data for testBuiltinFactory().
   */
  public function providerTestBuiltinFactory(): iterable {
    yield ['null://null', NullTransport::class];
    yield ['sendmail://default', SendmailTransport::class];
    yield ['smtp://default', EsmtpTransport::class];
  }

  /**
   * @covers ::getTransport
   */
  public function testMissingFactory() {
    $manager = $this->container->get('mailer.transport_manager');
    assert($manager instanceof TransportManagerInterface);

    $this->expectException(UnsupportedSchemeException::class);
    $this->expectExceptionMessage('The "drupal.no-transport" scheme is not supported');
    $manager->getTransport('drupal.no-transport://default');
  }

  /**
   * @covers ::addTransportFactory
   */
  public function testThirdPartyFactory() {
    $this->enableModules(['mailer_transport_manager_test']);

    $manager = $this->container->get('mailer.transport_manager');
    assert($manager instanceof TransportManagerInterface);

    $actual = $manager->getTransport('drupal.test-canary://default');
    $this->assertInstanceOf(CanaryTransport::class, $actual);
  }

}
