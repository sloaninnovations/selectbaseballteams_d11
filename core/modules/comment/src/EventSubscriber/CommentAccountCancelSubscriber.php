<?php

namespace Drupal\comment\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\Event\AccountCancelEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Performs comment module operations when a user account is cancelled.
 */
class CommentAccountCancelSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
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
   *
   * @todo Convert to a batch process in #3007581.
   * @see https://www.drupal.org/project/drupal/issues/3007581
   */
  public function onUserAccountCancel(AccountCancelEvent $event): void {
    $method = $event->getMethod();
    if (!in_array($method, [
      'user_cancel_block_unpublish',
      'user_cancel_reassign',
    ], TRUE)) {
      return;
    }

    /** @var \Drupal\comment\CommentInterface[] $comments */
    $comments = $this->entityTypeManager->getStorage('comment')->loadByProperties([
      'uid' => $event->getAccount()->id(),
    ]);
    $anonymous_name = $this->configFactory->get('user.settings')->get('anonymous');
    foreach ($comments as $comment) {
      if ($method === 'user_cancel_block_unpublish') {
        $comment->setUnpublished()->save();
      }
      else {
        $comment->setOwnerId(0)->setAuthorName($anonymous_name)->save();
      }
    }
  }

}
