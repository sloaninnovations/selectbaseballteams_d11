<?php

namespace Drupal\Component\EventDispatcher;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a factory returning the event dispatcher service.
 */
abstract class EventDispatcherFactory {

  private static EventDispatcherInterface $eventDispatcher;

  public static function getInstance(): EventDispatcherInterface {
    if (!isset(self::$eventDispatcher)) {
      self::$eventDispatcher = new EventDispatcher();
    }
    return self::$eventDispatcher;
  }

}
