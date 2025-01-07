<?php

declare(strict_types = 1);

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
  ) {
    parent::__construct($tempStoreFactory);
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
