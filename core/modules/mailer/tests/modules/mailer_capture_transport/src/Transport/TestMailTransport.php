<?php

namespace Drupal\mailer_capture_transport\Transport;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

/**
 * Defines a mail transport that captures sent messages in the state system.
 *
 * This class is for running tests or for development.
 */
class TestMailTransport extends AbstractTransport implements TransportInterface {

  /**
   * {@inheritdoc}
   */
  protected function doSend(SentMessage $message): void {
    $captured_emails = \Drupal::state()->get('system.test_mail_transport', []);
    $captured_emails[] = $message;
    \Drupal::state()->set('system.test_mail_transport', $captured_emails);

    $originalMessage = $message->getOriginalMessage();
    if (
      $originalMessage instanceof Email &&
      $originalMessage->getHeaders()->has('X-Drupal-Test-Mail-Transport-Fail')
    ) {
      throw new \RuntimeException('This message failed in transport');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return 'drupal.test-mail';
  }

}
