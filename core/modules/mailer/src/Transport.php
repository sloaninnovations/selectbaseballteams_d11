<?php

namespace Drupal\mailer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\Mailer\Transport as SymfonyTransport;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * The mailer transport factory.
 *
 * Adapts the symfony mailer Transport factory class to better suit the Drupal
 * config system.
 *
 * @see \Symfony\Component\Mailer\Transport
 *
 * @internal
 */
class Transport {

  /**
   * Ordered list of mailer transport factories.
   *
   * @var \Symfony\Component\Mailer\Transport\TransportFactoryInterface[]
   */
  protected array $transportFactories;

  /**
   * Registers a transport factory.
   *
   * @param \Symfony\Component\Mailer\Transport\TransportFactoryInterface $transportFactory
   *   A transport factory.
   */
  public function addTransportFactory(TransportFactoryInterface $transportFactory) {
    $this->transportFactories[] = $transportFactory;
  }

  /**
   * Return configured transport.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   *
   * @return \Symfony\Component\Mailer\Transport\TransportInterface
   */
  public function fromConfig(ConfigFactoryInterface $configFactory): TransportInterface {
    $symfonyTransport = new SymfonyTransport($this->transportFactories);
    $dsn = $configFactory->get('system.mail')->get('mailer_dsn');
    return $symfonyTransport->fromString($dsn);
  }

}
