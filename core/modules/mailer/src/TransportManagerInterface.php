<?php

namespace Drupal\mailer;

use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Defines an interface for mailer transport managers.
 */
interface TransportManagerInterface {

  /**
   * Registers a transport factory.
   *
   * @param \Symfony\Component\Mailer\Transport\TransportFactoryInterface $transportFactory
   *   A transport factory.
   */
  public function addTransportFactory(TransportFactoryInterface $transportFactory);

  /**
   * Return configured transport.
   *
   * @param string|null $dsn
   *   The transport DSN, defaults to system.mail mailer_dsn config value.
   *
   * @return \Symfony\Component\Mailer\Transport\TransportInterface
   */
  public function getTransport(#[\SensitiveParameter] ?string $dsn = NULL): TransportInterface;

}
