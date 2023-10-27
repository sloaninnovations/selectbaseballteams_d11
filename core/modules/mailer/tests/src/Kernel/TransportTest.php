<?php

namespace Drupal\Tests\mailer\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\mailer_transport_manager_test\Mailer\Transport\CanaryTransport;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

/**
 * Tests the transport factory service.
 *
 * @group mailer
 * @coversDefaultClass \Drupal\mailer\Transport
 */
class TransportTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['mailer', 'system'];

  /**
   * Sets up a mailer dsn config override.
   *
   * @param string $dsn
   *   The transport dsn string.
   */
  protected function setUpMailerDsnConfigOverride(string $dsn): void {
    $GLOBALS['config']['system.mail']['mailer_dsn'] = $dsn;
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $GLOBALS['config']['system.mail']['mailer_dsn'] = 'null://null';
    parent::tearDown();
  }

  /**
   * @covers ::fromConfig
   */
  public function testDefaultTestMailFactory(): void {
    $actual = $this->container->get('mailer.transport');
    $this->assertInstanceOf(NullTransport::class, $actual);
  }

  /**
   * @dataProvider providerTestBuiltinFactory
   * @covers ::fromConfig
   */
  public function testBuiltinFactory(string $dsn, string $expected): void {
    $this->setUpMailerDsnConfigOverride($dsn);

    $actual = $this->container->get('mailer.transport');
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
   * @covers ::fromConfig
   */
  public function testSendmailFactoryAllowedCommand(): void {
    // Test sendmail command allowlist.
    $settings = Settings::getAll();
    $settings['mailer_sendmail_commands'] = ['/usr/local/bin/sendmail -bs'];
    new Settings($settings);

    // Test allowlisted command.
    $this->setUpMailerDsnConfigOverride('sendmail://default?command=/usr/local/bin/sendmail%20-bs');
    $actual = $this->container->get('mailer.transport');
    $this->assertInstanceOf(SendmailTransport::class, $actual);
  }

  /**
   * @covers ::fromConfig
   */
  public function testSendmailFactoryUnlistedCommand(): void {
    // Test sendmail command allowlist.
    $settings = Settings::getAll();
    $settings['mailer_sendmail_commands'] = ['/usr/local/bin/sendmail -bs'];
    new Settings($settings);

    // Test unlisted command.
    $this->setUpMailerDsnConfigOverride('sendmail://default?command=/usr/bin/bc');
    $this->expectExceptionMessage("Unsafe sendmail command /usr/bin/bc");
    $this->container->get('mailer.transport');
  }

  /**
   * @covers ::fromConfig
   */
  public function testMissingFactory(): void {
    $this->setUpMailerDsnConfigOverride('drupal.no-transport://default');

    $this->expectExceptionMessage('The "drupal.no-transport" scheme is not supported');
    $this->container->get('mailer.transport');
  }

  /**
   * @covers ::addTransportFactory
   */
  public function testThirdPartyFactory(): void {
    $this->enableModules(['mailer_transport_manager_test']);

    $this->setUpMailerDsnConfigOverride('drupal.test-canary://default');

    $actual = $this->container->get('mailer.transport');
    $this->assertInstanceOf(CanaryTransport::class, $actual);
  }

}
