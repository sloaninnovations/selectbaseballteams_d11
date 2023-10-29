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
   * An unsorted array of arrays of transport factories.
   *
   * An associative array. The keys are integers that indicate priority. Values
   * are arrays of TransportFactoryInterface objects.
   *
   * @var \Symfony\Component\Mailer\Transport\TransportFactoryInterface[][]
   */
  protected array $transportFactories;

  /**
   * Registers a transport factory.
   *
   * @param \Symfony\Component\Mailer\Transport\TransportFactoryInterface $transportFactory
   *   A transport factory.
   * @param int $priority
   *   The priority of the transport factory being added.
   */
  public function addTransportFactory(TransportFactoryInterface $transportFactory, ?int $priority = 0): void {
    $this->transportFactories[$priority][] = $transportFactory;
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
    krsort($this->transportFactories);
    $sortedFactories = array_merge(...$this->transportFactories);
    $symfonyTransport = new SymfonyTransport($sortedFactories);
    $dsn = $configFactory->get('system.mail')->get('mailer_dsn');
    return $symfonyTransport->fromString($dsn);
  }

}
