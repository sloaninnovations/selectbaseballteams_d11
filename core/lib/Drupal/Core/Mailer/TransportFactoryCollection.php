<?php

namespace Drupal\Core\Mailer;

use Symfony\Component\Mailer\Transport\TransportFactoryInterface;

/**
 * The mailer transport factory collection.
 *
 * Collects transport factories from the container. Pass an instance of this
 * class to the constructor of the Symfony mailer Transport facade.
 *
 * @todo Deprecate this class and replace mailer.transport_factory constructor
 *   argument with !tagged_iterator as soon as Drupal dependency injection
 *   caugth up with the upstream component (#3228629).
 * @see https://www.drupal.org/project/drupal/issues/3228629
 * @see \Symfony\Component\Mailer\Transport
 *
 * @internal
 */
final class TransportFactoryCollection implements \IteratorAggregate {

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
   * {@inheritdoc}
   */
  public function getIterator(): \Traversable {
    krsort($this->transportFactories);
    $sortedFactories = array_merge(...$this->transportFactories);
    yield from $sortedFactories;
  }

}
