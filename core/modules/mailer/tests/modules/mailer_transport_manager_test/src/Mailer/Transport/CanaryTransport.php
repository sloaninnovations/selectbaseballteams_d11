<?php

namespace Drupal\mailer_transport_manager_test\Mailer\Transport;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * A transport only used to test the transport manager.
 */
class CanaryTransport extends AbstractTransport implements TransportInterface {

  /**
   * {@inheritdoc}
   */
  protected function doSend(SentMessage $message): void {
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return 'drupal.test-canary://default';
  }

}
