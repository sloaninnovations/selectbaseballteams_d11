<?php

namespace Drupal\workspaces;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\RevisionableInterface;

/**
 * Workspace-aware entity repository to deliver the most recent revisions,
 * taking workspaces into account.
 */
class WorkspacesEntityRepository extends EntityRepository {

  /**
   * Get the latest revision for editing.
   *
   * In core this means the latest revision. But in workspaces_parallel we need
   * to get the latest revision in the current workspace, not simply the latest.
   *
   * If there is no workspace, we need to get the latest revision not in a
   * workspace.
   */
  public function getActiveMultiple($entity_type_id, array $entity_ids, array $contexts = NULL): array {
    if (!\Drupal::config('workspaces.settings')->get('parallel')) {
      // No support for parallel editing.
      return parent::getActiveMultiple($entity_type_id, $entity_ids, $contexts);
    }

    /** @var \Drupal\workspaces\WorkspaceInformationInterface $workspaceInformation */
    $workspaceInformation = \Drupal::service('workspaces.information');

    if (!$workspaceInformation->isEntityTypeSupported($this->entityTypeManager->getDefinition($entity_type_id))) {
      // Entity not supported by Workspaces.
      return parent::getActiveMultiple($entity_type_id, $entity_ids, $contexts);
    }

    $active = [];

    $entities = $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->loadMultiple($entity_ids);

    foreach ($entities as $id => $entity) {
      // Retrieve the "latest revision", taking parallel Workspaces into
      // account.
      $active[$id] = $this->getLatestWorkspaceAwareRevision($entity);
    }

    return $active;
  }

  /**
   * Instead of getting the latest revision, get the latest revision but only
   * within the current workspace. If it is not in a workspace, then get the
   * latest revision that is not associated with a workspace.
   *
   * @param $entity
   *   A workspace-supported entity.
   */
  private function getLatestWorkspaceAwareRevision(RevisionableInterface $entity) {
    $entityTypeManager = \Drupal::entityTypeManager();

    /** @var \Drupal\workspaces\WorkspaceManagerInterface $workspaceManager */
    $workspaceManager = \Drupal::service('workspaces.manager');
    /** @var \Drupal\workspaces\WorkspaceAssociationInterface $workspaceAssociation */
    $workspaceAssociation = \Drupal::service('workspaces.association');

    // Get a list of revision IDs for entities that have a revision set for the
    // current active workspace. If an entity has multiple revisions set for a
    // workspace, only the one with the highest ID is returned.
    if ($workspaceManager->getActiveWorkspace() && $tracked_entities = $workspaceAssociation->getTrackedEntities($workspaceManager->getActiveWorkspace()->id(), $entity->getEntityTypeId(), [$entity->id()])) {
      /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
      $storage = $entityTypeManager->getStorage($entity->getEntityTypeId());

      $vid = key($tracked_entities[$entity->getEntityTypeId()]);

      // Swap out the entity.
      return $storage->loadRevision($vid);
    }

    // If there isn't an active workspace, we still do not want to return the
    // latest revision. Get the latest non-workspace revision.
    return $this->getLatestNonWorkspaceRevision($entity);
  }

  /**
   * Get the latest revision of an entity not associated with a workspace.
   */
  private function getLatestNonWorkspaceRevision(RevisionableInterface $entity): ?RevisionableInterface {
    $storage = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId());

    /** @var ContentEntityTypeInterface $entityType */
    $entityType = $entity->getEntityType();
    $revisionTable = $entityType->getRevisionTable();
    $revisionMetadataKey = $entityType->getKey('revision');
    $entityIdKey = $entityType->getKey('id');

    $query = \Drupal::database()->select($revisionTable);
    $query
      ->fields($revisionTable, [$revisionMetadataKey])
      ->orderBy($revisionMetadataKey, 'DESC')
      ->range(0, 1)
      ->condition($entityIdKey, $entity->id())
      ->isNull('workspace');

    if ($vid = $query->execute()->fetchField()) {
      /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
      return $storage->loadRevision($vid);
    }
    else {
      // Workspace supported, but no other revisions associated with a
      // workspace, so nothing to do here. Return the entity as-is.
      return $entity;
    }
  }

}
