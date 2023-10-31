<?php

namespace Drupal\mailer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * The default mailer transport factory adapter.
 *
 * Adapts the Symfony mailer transport factory class to better suit the Drupal
 * config system.
 *
 * @see \Symfony\Component\Mailer\Transport
 *
 * @internal
 */
final class DefaultFactoryAdapter {

  /**
   * Constructs new transport factory adapter.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Symfony\Component\Mailer\Transport $transport
   *   The Symfony mailer transport factory.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected Transport $transport
  ) {
  }

  /**
   * Returns a transport constructed using the default DSN.
   *
   * @return \Symfony\Component\Mailer\Transport\TransportInterface
   */
  public function fromDefaultDsn(): TransportInterface {
    $dsn = $this->configFactory->get('system.mail')->get('mailer_dsn');
    return $this->transport->fromString($dsn);
  }

}
