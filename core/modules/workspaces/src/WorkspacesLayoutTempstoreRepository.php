<?php

namespace Drupal\workspaces;

use Drupal\layout_builder\LayoutTempstoreRepository;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides a mechanism for loading workspace specific layout changes.
 */
class WorkspacesLayoutTempstoreRepository extends LayoutTempstoreRepository {

  public function __construct(
    protected $tempStoreFactory,
    protected readonly WorkspaceManagerInterface $workspaceManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  protected function getKey(SectionStorageInterface $section_storage) {
    $key = parent::getKey($section_storage);
    return $this->getWorkspaceAwareKey($key);
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

}
