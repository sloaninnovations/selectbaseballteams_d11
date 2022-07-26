<?php

namespace Drupal\user_cancel_test;

use Drupal\user\Event\AccountCancelEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Performs user_cancel_test module operations when a user account is cancelled.
 */
class UserCancelTestAccountCancelSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // Act before all Drupal core subscribers.
      // @see \Drupal\user\EventSubscriber\AccountCancelSubscriber::onUserAccountCancel()
      AccountCancelEvent::class => ['onUserAccountCancel', 50],
    ];
  }

  /**
   * Acts on user account cancel event.
   *
   * @param \Drupal\user\Event\AccountCancelEvent $event
   *   The user cancel event.
   */
  public function onUserAccountCancel(AccountCancelEvent $event): void {
    if ($event->getMethod() === 'user_cancel_test') {
      \Drupal::messenger()->addStatus('Custom user cancel method executed.');
      $event->stopPropagation();
    }
  }

}
