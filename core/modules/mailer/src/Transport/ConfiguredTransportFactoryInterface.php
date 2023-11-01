<?php

namespace Drupal\mailer\Transport;

use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * An interface defining mailer transport factory classes.
 */
interface ConfiguredTransportFactoryInterface {

  /**
   * Creates and returns a configured mailer transport class.
   */
  public function createTransport(): TransportInterface;

}
