<?php

namespace Drupal\Core\Field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides en entity access control handler for base field override entity.
 */
class BaseFieldOverrideAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return parent::checkAccess($entity, $operation, $account)
      ->orAllowedIf(function (CacheableMetadata $cacheability) use ($account, $entity) {
        $cacheability->addCacheContexts(['user.permissions']);
        return $account->hasPermission('administer ' . $entity->getTargetEntityTypeId() . ' fields');
      });
  }

}
