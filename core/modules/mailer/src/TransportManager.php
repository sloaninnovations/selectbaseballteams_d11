<?php

namespace Drupal\mailer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Default implementation of the mailer transport manager.
 */
class TransportManager implements TransportManagerInterface {

  /**
   * Ordered list of mailer transport factories.
   *
   * @var \Symfony\Component\Mailer\Transport\TransportFactoryInterface[]
   */
  protected array $transportFactories;

  /**
   * Constructs the default mailer transport manager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function addTransportFactory(TransportFactoryInterface $transportFactory) {
    $this->transportFactories[] = $transportFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransport(#[\SensitiveParameter] ?string $dsn = NULL): TransportInterface {
    if (!isset($dsn)) {
      $dsn = $this->configFactory->get('system.mail')->get('mailer_dsn');
    }

    $transportFactory = new Transport($this->transportFactories);
    return $transportFactory->fromString($dsn);
  }

}
