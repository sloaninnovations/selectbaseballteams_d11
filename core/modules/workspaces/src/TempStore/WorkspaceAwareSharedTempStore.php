<?php

namespace Drupal\workspaces\TempStore;

use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\Lock;
use Drupal\Core\TempStore\SharedTempStore;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Extends the default implementation to store/retrieve workspace specific data.
 */
class WorkspaceAwareSharedTempStore extends SharedTempStore {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    protected WorkspaceManagerInterface $workspaceManager,
    KeyValueStoreExpirableInterface $storage,
    LockBackendInterface $lock_backend,
    $owner,
    RequestStack $request_stack,
    AccountProxyInterface $current_user,
    $expire = 604800
  ) {
    parent::__construct($storage, $lock_backend, $owner, $request_stack, $current_user, $expire);
  }

  /**
   * Suffixes given temp store key with a workspace ID when one is active.
   */
  protected function getWorkspaceAwareKey(string $key): string {
    if ($this->workspaceManager->hasActiveWorkspace()) {
      $key .= $this->workspaceManager->getActiveWorkspace()->id();
    }
    return $key;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    parent::set($this->getWorkspaceAwareKey($key), $value);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    parent::delete($this->getWorkspaceAwareKey($key));
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    return parent::get($this->getWorkspaceAwareKey($key));
  }

  /**
   * {@inheritdoc}
   */
  public function getIfOwner($key) {
    return parent::getIfOwner($this->getWorkspaceAwareKey($key));
  }

  /**
   * {@inheritdoc}
   */
  public function setIfNotExists($key, $value) {
    return parent::setIfNotExists($this->getWorkspaceAwareKey($key), $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setIfOwner($key, $value) {
    return parent::setIfOwner($this->getWorkspaceAwareKey($key), $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata($key) {
    return parent::getMetadata($this->getWorkspaceAwareKey($key));
  }

}
