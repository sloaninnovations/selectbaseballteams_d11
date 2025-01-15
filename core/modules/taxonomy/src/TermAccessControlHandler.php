<?php

namespace Drupal\taxonomy;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the taxonomy term entity type.
 *
 * @see \Drupal\taxonomy\Entity\Term
 */
class TermAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a TermAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer taxonomy')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        $access_result = AccessResult::allowedIf($account->hasPermission('access content') && $entity->isPublished())
          ->cachePerPermissions()
          ->addCacheableDependency($entity);
        if (!$access_result->isAllowed()) {
          $access_result->setReason("The 'access content' permission is required and the taxonomy term must be published.");
        }
        return $access_result;

      case 'update':
        if ($account->hasPermission("edit terms in {$entity->bundle()}")) {
          return AccessResult::allowed()->cachePerPermissions();
        }

        return AccessResult::neutral()->setReason("The following permissions are required: 'edit terms in {$entity->bundle()}' OR 'administer taxonomy'.");

      case 'delete':
        if ($account->hasPermission("delete terms in {$entity->bundle()}")) {
          return AccessResult::allowed()->cachePerPermissions();
        }

        return AccessResult::neutral()->setReason("The following permissions are required: 'delete terms in {$entity->bundle()}' OR 'administer taxonomy'.");

      case 'view revision':
      case 'view all revisions':
        if ($account->hasPermission("view term revisions in {$entity->bundle()}") || $account->hasPermission("view all taxonomy revisions")) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral()->setReason("The following permissions are required: 'view revisions in {$entity->bundle()}' OR 'view all taxonomy revisions'.");

      case 'revert':
        if (($account->hasPermission("revert term revisions in {$entity->bundle()}") && $account->hasPermission("edit terms in {$entity->bundle()}")) || $account->hasPermission("revert all taxonomy revisions")) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral()->setReason("The following permissions are required: 'revert term revisions in {$entity->bundle()}' OR 'revert all taxonomy revisions'.");

      case 'delete revision':
        if (($account->hasPermission("delete term revisions in {$entity->bundle()}") && $account->hasPermission("delete terms in {$entity->bundle()}")) || $account->hasPermission("delete all taxonomy revisions")) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral()->setReason("The following permissions are required: 'delete term revisions in {$entity->bundle()}' OR 'delete all taxonomy revisions'.");

      case 'view all revisions':
        // Perform basic permission checks first.
        if (!$account->hasPermission('view all taxonomy revisions')) {
          return AccessResult::neutral("The 'view all taxonomy revisions' permission is required.")->cachePerPermissions();
        }

        // First check the access to the default revision and finally, if the
        // taxonomy passed in is not the default revision then access to that,
        // too.
        $taxonomy_storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
        $access = $this->access($taxonomy_storage->load($entity->id()), 'view', $account, TRUE);
        if (!$entity->isDefaultRevision()) {
          $access = $access->andIf($this->access($entity, 'view', $account, TRUE));
        }
        return $access->cachePerPermissions()->addCacheableDependency($entity);

      default:
        // No opinion.
        return AccessResult::neutral()->cachePerPermissions();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ["create terms in $entity_bundle", 'administer taxonomy'], 'OR');
  }

}
