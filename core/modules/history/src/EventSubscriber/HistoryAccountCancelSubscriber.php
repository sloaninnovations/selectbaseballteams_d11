<?php

namespace Drupal\history\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\user\Event\AccountCancelEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Performs history module operations when a user account is cancelled.
 */
class HistoryAccountCancelSubscriber implements EventSubscriberInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // Act before AccountCancelSubscriber::onUserAccountCancel()
      // @see \Drupal\user\EventSubscriber\AccountCancelSubscriber::onUserAccountCancel()
      AccountCancelEvent::class => ['onUserAccountCancel', 20],
    ];
  }

  /**
   * Acts on user account cancel event.
   *
   * @param \Drupal\user\Event\AccountCancelEvent $event
   *   The user cancel event.
   */
  public function onUserAccountCancel(AccountCancelEvent $event): void {
    if ($event->getMethod() === 'user_cancel_reassign') {
      $this->database->delete('history')
        ->condition('uid', $event->getAccount()->id())
        ->execute();
    }
  }

}
