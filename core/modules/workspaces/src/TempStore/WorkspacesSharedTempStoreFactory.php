<?php

namespace Drupal\workspaces\TempStore;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\SharedTempStore;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\workspaces\WorkspaceInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Decorates the shared temporary storage to enable workspace specific storage.
 *
 * We can't use our own class here because there is no interface for
 * SharedTempStoreFactory and many constructors of classes that use the service
 * have a SharedTempStoreFactory type.
 */
class WorkspacesSharedTempStoreFactory extends SharedTempStoreFactory {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Sets the workspace manager.
   *
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager service.
   *
   * @return $this
   */
  public function setWorkspacesManager(WorkspaceManagerInterface $workspace_manager) {
    $this->workspaceManager = $workspace_manager;
    return $this;
  }

  /**
   * Creates a SharedTempStore for the current user or anonymous session.
   *
   * If this is called inside a workspace, the collection key is suffixed with
   * the workspace ID to enable workspace specific storage.
   *
   * @param string $collection
   *   The collection name to use for this key/value store. This is typically
   *   a shared namespace or module name, e.g. 'views', 'entity', etc.
   * @param mixed $owner
   *   (optional) The owner of this SharedTempStore. By default, the
   *   SharedTempStore is owned by the currently authenticated user, or by the
   *   active anonymous session if no user is logged in.
   *
   * @return \Drupal\Core\TempStore\SharedTempStore
   *    An instance of the key/value store.
   */
  public function get($collection, $owner = NULL) {
    // Use the currently authenticated user ID or the active user ID unless
    // the owner is overridden.
    if (!isset($owner)) {
      $owner = $this->currentUser->id();
      if ($this->currentUser->isAnonymous()) {
        $owner = $this->requestStack->getSession()->get('core.tempstore.shared.owner', Crypt::randomBytesBase64());
      }
    }

    $storage = $this->storageFactory->get("tempstore.shared.$collection");
    return new WorkspaceAwareSharedTempStore(
      $this->workspaceManager,
      $storage,
      $this->lockBackend,
      $owner,
      $this->requestStack,
      $this->currentUser,
      $this->expire
    );
  }

}
