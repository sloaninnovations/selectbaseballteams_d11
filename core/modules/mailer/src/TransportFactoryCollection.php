<?php

namespace Drupal\mailer;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;

/**
 * The mailer transport factory collection.
 *
 * Collects transport factories from the container and constructs the symfony
 * transport factory.
 *
 * @see \Symfony\Component\Mailer\Transport
 *
 * @internal
 */
final class TransportFactoryCollection {

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
   * Returns the Symfony mailer transport factory.
   *
   * @return \Symfony\Component\Mailer\Transport
   */
  public function createTransportFactory(): Transport {
    krsort($this->transportFactories);
    $sortedFactories = array_merge(...$this->transportFactories);
    return new Transport($sortedFactories);
  }

}
