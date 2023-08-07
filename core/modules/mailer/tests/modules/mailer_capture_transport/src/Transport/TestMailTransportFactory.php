<?php

namespace Drupal\mailer_capture_transport\Transport;

use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Defines a mail transport factory for the test mail transport.
 *
 * This class is for running tests or for development.
 */
class TestMailTransportFactory extends AbstractTransportFactory {

  /**
   * {@inheritdoc}
   */
  public function create(Dsn $dsn): TransportInterface {
    if ($dsn->getScheme() === 'drupal.test-mail') {
      return new TestMailTransport($this->dispatcher, $this->logger);
    }

    throw new UnsupportedSchemeException($dsn, 'drupal_test', $this->getSupportedSchemes());
  }

  /**
   * {@inheritdoc}
   */
  protected function getSupportedSchemes(): array {
    return ['drupal.test-mail'];
  }

}
