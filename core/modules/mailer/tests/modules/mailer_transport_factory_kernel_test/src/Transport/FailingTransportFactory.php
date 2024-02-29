<?php

declare(strict_types=1);

namespace Drupal\mailer_transport_factory_kernel_test\Transport;

use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * A failing transport factory with the same scheme as CanaryTransportFactory.
 *
 * This transport factory is used to ensure that transport factories are tried
 * in reversed order of the priority they are registered with in the container.
 */
class FailingTransportFactory extends AbstractTransportFactory implements TransportFactoryInterface {

  /**
   * {@inheritdoc}
   */
  protected function getSupportedSchemes(): array {
    return ['drupal.test-canary'];
  }

  /**
   * {@inheritdoc}
   */
  public function create(Dsn $dsn): TransportInterface {
    throw new \BadMethodCallException('Not implemented');
  }

}
