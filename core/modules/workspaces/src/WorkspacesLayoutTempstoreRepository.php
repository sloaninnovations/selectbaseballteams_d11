<?php

declare(strict_types = 1);

namespace Drupal\workspaces;

use Drupal\layout_builder\LayoutTempstoreRepository;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides a mechanism for loading workspace specific layout changes.
 */
class WorkspacesLayoutTempstoreRepository extends LayoutTempstoreRepository {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected WorkspaceManagerInterface $workspaceManager;

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
   * {@inheritdoc}
   */
  protected function getKey(SectionStorageInterface $section_storage) {
    $key = parent::getKey($section_storage);
    // Suffix the layout tempstore key with a workspace ID when one is active.
    if ($this->workspaceManager->hasActiveWorkspace()) {
      $key .= $this->workspaceManager->getActiveWorkspace()->id();
    }
    return $key;
  }

}
