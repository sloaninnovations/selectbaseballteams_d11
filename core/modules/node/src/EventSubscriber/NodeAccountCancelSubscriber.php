<?php

namespace Drupal\node\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\Event\AccountCancelEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Performs node module operations when a user account is cancelled.
 */
class NodeAccountCancelSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
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
    $method = $event->getMethod();
    if ($method === 'user_cancel_block_unpublish') {
      // Unpublish nodes (current revisions).
      $nids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $event->getAccount()->id())
        ->execute();
      $this->moduleHandler->loadInclude('node', 'inc', 'node.admin');
      node_mass_update($nids, ['status' => 0], NULL, TRUE);
    }
    elseif ($method === 'user_cancel_reassign') {
      // Anonymize all the nodes for this old account.
      $vids = $this->entityTypeManager->getStorage('node')->userRevisionIds($event->getAccount());
      $this->moduleHandler->loadInclude('node', 'inc', 'node.admin');
      node_mass_update($vids, [
        'uid' => 0,
        'revision_uid' => 0,
      ], NULL, TRUE, TRUE);
    }
  }

}
