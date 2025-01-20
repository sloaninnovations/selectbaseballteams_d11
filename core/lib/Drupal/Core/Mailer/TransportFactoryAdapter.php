<?php

declare(strict_types=1);

namespace Drupal\Core\Mailer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mailer\Transport\ConfiguredTransportFactoryInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * The default mailer transport factory adapter.
 *
 * Adapts the Symfony mailer transport factory class to better suit the Drupal
 * config system.
 *
 * @see \Symfony\Component\Mailer\Transport
 */
class TransportFactoryAdapter implements ConfiguredTransportFactoryInterface {

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
    protected Transport $transport,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function createTransport(): TransportInterface {
    $dsn = $this->configFactory->get('system.mail')->get('mailer_dsn');
    $dsnObject = new Dsn(...$dsn);
    return $this->transport->fromDsnObject($dsnObject);
  }

}
