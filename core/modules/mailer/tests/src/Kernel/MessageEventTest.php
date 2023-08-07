<?php

namespace Drupal\Tests\mailer\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

/**
 * Tests the message events.
 *
 * @group mailer
 */
class MessageEventTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['mailer', 'system', 'mailer_capture_transport'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setupMailCaptureTransport();
    $this->config('system.site')
      ->set('langcode', 'en')
      ->set('mail', 'site-mail@example.com')
      ->set('name', 'Example Site')
      ->save();
  }

  /**
   * Ensure that a SentMessageEvent is fired whenever deliver fails.
   */
  public function testSentMessageEvent(): void {
    $dispatcher = $this->container->get('event_dispatcher');
    assert($dispatcher instanceof EventDispatcherInterface);

    $messageEvents = 0;
    $dispatcher->addListener(MessageEvent::class, function () use (&$messageEvents) {
      $messageEvents++;
    });

    $failedEvents = 0;
    $dispatcher->addListener(FailedMessageEvent::class, function () use (&$failedEvents) {
      $failedEvents++;
    });

    $sentEvents = 0;
    $dispatcher->addListener(SentMessageEvent::class, function () use (&$sentEvents) {
      $sentEvents++;
    });

    $email = (new Email())
      ->subject('Eight grow real success?')
      ->text('City something catch pay camera ability. Decide democratic word!');
    $email->getHeaders()->addTextHeader('X-Drupal-Test-Mail-Transport-Fail', '');

    $transport = $this->container->get('mailer.transports');
    assert($transport instanceof TransportInterface);

    $this->expectExceptionMessage('This message failed in transport');
    $transport->send($email->to('foobar@example.com'));

    $this->assertEquals(1, $messageEvents);
    $this->assertEquals(1, $failedEvents);
    $this->assertEquals(0, $sentEvents);
  }

  /**
   * Ensure that a FailedMessageEvent is fired whenever deliver fails.
   */
  public function testFailedMessageEvent(): void {
    $dispatcher = $this->container->get('event_dispatcher');
    assert($dispatcher instanceof EventDispatcherInterface);

    $messageEvents = 0;
    $dispatcher->addListener(MessageEvent::class, function () use (&$messageEvents) {
      $messageEvents++;
    });

    $failedEvents = 0;
    $dispatcher->addListener(FailedMessageEvent::class, function () use (&$failedEvents) {
      $failedEvents++;
    });

    $sentEvents = 0;
    $dispatcher->addListener(SentMessageEvent::class, function () use (&$sentEvents) {
      $sentEvents++;
    });

    $email = (new Email())
      ->subject('Notice soon as brother')
      ->text('House answer start behind. Around medical also its attorney before interesting step. Water piece on artist.');

    $transport = $this->container->get('mailer.transports');
    assert($transport instanceof TransportInterface);

    $transport->send($email->to('foobar@example.com'));

    $this->assertEquals(1, $messageEvents);
    $this->assertEquals(0, $failedEvents);
    $this->assertEquals(1, $sentEvents);
  }

  /**
   * Ensure that a MessageEvent subscriber can reject a message.
   */
  public function testRejectMessage(): void {
    $dispatcher = $this->container->get('event_dispatcher');
    assert($dispatcher instanceof EventDispatcherInterface);

    $messageEvents = 0;
    $dispatcher->addListener(MessageEvent::class, function (MessageEvent $event) use (&$messageEvents) {
      $messageEvents++;
      $event->reject();
    });

    $failedEvents = 0;
    $dispatcher->addListener(FailedMessageEvent::class, function () use (&$failedEvents) {
      $failedEvents++;
    });

    $sentEvents = 0;
    $dispatcher->addListener(SentMessageEvent::class, function () use (&$sentEvents) {
      $sentEvents++;
    });

    $email = (new Email())
      ->subject('Have heart cover analysis carry!')
      ->text('Record rock college watch week institution collection anything. Media under opportunity similar.');

    $transport = $this->container->get('mailer.transports');
    assert($transport instanceof TransportInterface);

    $result = $transport->send($email->to('foobar@example.com'));
    $this->assertNull($result);

    $this->assertEquals(1, $messageEvents);
    $this->assertEquals(0, $failedEvents);
    $this->assertEquals(0, $sentEvents);
  }

  /**
   * Sets up mail capture transport.
   */
  protected function setupMailCaptureTransport(): void {
    // Sets up mail capture transport in the child site.
    $this->config('system.mail')
      ->set('mailer_dsn', 'drupal.test-mail://default')
      ->save();

    // Sets up mail capture transport in the test runner.
    $GLOBALS['config']['system.mail']['mailer_dsn'] = 'drupal.test-mail://default';
  }

}
