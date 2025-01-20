<?php

declare(strict_types=1);

namespace Drupal\Core\Mailer\Transport;

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
