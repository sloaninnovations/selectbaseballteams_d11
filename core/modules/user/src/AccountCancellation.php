<?php

namespace Drupal\user;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Event\AccountCancelEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Provides user account cancellation functionality.
 *
 * Third-party, having something to say on user cancellation, should subscribe
 * to \Drupal\user\Event\AccountCancelEvent event. The 'user' module provides
 * its own subscriber (\Drupal\user\EventSubscriber\AccountCancelSubscriber)
 * that actually performs the cancellation. A subscriber aiming to implement its
 * own user account cancellation logic and avoid default core behavior should
 * set a higher priority and bypass downstream subscribers by stopping the event
 * propagation.
 *
 * @see \Drupal\user\Event\AccountCancelEvent
 * @see \Drupal\user\EventSubscriber\AccountCancelSubscriber
 */
class AccountCancellation implements AccountCancellationInterface {

  use StringTranslationTrait;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * Constructs a new service instance.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EventDispatcherInterface $event_dispatcher) {
    $this->moduleHandler = $module_handler;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function cancel(UserInterface $account, string $method, array $context = []): void {
    // Initialize batch (to set title).
    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Cancelling account'));
    batch_set($batch_builder->toArray());

    // When the 'user_cancel_delete' method is used, the user entity is deleted,
    // which invokes hook_ENTITY_TYPE_predelete() and hook_ENTITY_TYPE_delete().
    // Modules should use those hooks to respond to the account deletion.
    if ($method !== 'user_cancel_delete') {
      // Allow modules to add further sets to this batch.
      $description = 'The hook is deprecated in drupal:10.0.0 and is removed from drupal:11.0.0. In order to act on user account cancellation provide an event subscriber that listens to the \Drupal\user\Event\AccountCancelEvent event. The event subscriber can be defined with a priority higher than the core subscribers in order to cancel them by using AccountCancelEvent::stopPropagation(). See https://www.drupal.org/node/3279455';
      $this->moduleHandler->invokeAllDeprecated($description, 'user_cancel', [$context, $account, $method]);
    }

    // Allow third-party to add further sets to this batch.
    $account_cancel_event = new AccountCancelEvent($account, $method, $context);
    $this->eventDispatcher->dispatch($account_cancel_event);

    // After cancelling account, ensure that user is logged out.
    if ($account->id() == \Drupal::currentUser()->id()) {
      // Batch API stores data in the session, so use the finished operation to
      // manipulate the current user's session ID.
      $batch_builder = (new BatchBuilder())->setFinishCallback(static::class . '::regenerateSession');
      batch_set($batch_builder->toArray());
    }

    // Batch processing is either handled via Form API or has to be invoked
    // manually.
  }

  /**
   * Provides a finished batch processing callback for cancelling user account.
   *
   * Note that this method is declared static to avoid serialization of a huge
   * object by the batch API.
   */
  public static function regenerateSession(): void {
    // Regenerate the user's session instead of calling session_destroy() as we
    // want to preserve any messages that might have been set.
    \Drupal::service('session')->migrate();
  }

}
