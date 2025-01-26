<?php

namespace Drupal\Core\Entity\Entity\Access;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an entity access control handler for form displays.
 */
class EntityFormDisplayAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity */
    return parent::checkAccess($entity, $operation, $account)
      ->orAllowedIf(function (CacheableMetadata $cacheability) use ($account, $entity) {
        $cacheability->addCacheContexts(['user.permissions']);
        return $account->hasPermission('administer ' . $entity->getTargetEntityTypeId() . ' form display');
      });
  }

}
