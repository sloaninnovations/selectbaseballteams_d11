<?php

namespace Drupal\automated_cron\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * A subscriber running cron after a response is sent.
 */
class AutomatedCron implements EventSubscriberInterface {

  /**
   * The cron service closure.
   */
  protected \Closure $cronClosure;

  /**
   * The cron configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new automated cron runner.
   *
   * @param \Drupal\Core\CronInterface|\Closure $cron
   *   The cron service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key-value store service.
   */
  public function __construct(CronInterface|\Closure $cron, ConfigFactoryInterface $config_factory, StateInterface $state) {
    if ($cron instanceof CronInterface) {
      @trigger_error('Passing the $cron argument as an instance of type \Drupal\Core\CronInterface to ' . __METHOD__ . '() is deprecated in drupal:11.1.0 and type \Closure is required in drupal:12.0.0. Pass type \Closure instead. See https://www.drupal.org/node/3484001', E_USER_DEPRECATED);
      $cron = fn($cron) => $cron;
    }
    $this->cronClosure = $cron;
    $this->config = $config_factory->get('automated_cron.settings');
    $this->state = $state;
  }

  /**
   * Run the automated cron if enabled.
   *
   * @param \Symfony\Component\HttpKernel\Event\TerminateEvent $event
   *   The Event to process.
   */
  public function onTerminate(TerminateEvent $event) {
    $interval = $this->config->get('interval');
    if ($interval > 0) {
      $cron_next = $this->state->get('system.cron_last', 0) + $interval;
      if ((int) $event->getRequest()->server->get('REQUEST_TIME') > $cron_next) {
        $this->getCron()->run();
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents(): array {
    return [KernelEvents::TERMINATE => [['onTerminate', 100]]];
  }

  protected function getCron(): CronInterface {
    return ($this->cronClosure)();
  }
}
