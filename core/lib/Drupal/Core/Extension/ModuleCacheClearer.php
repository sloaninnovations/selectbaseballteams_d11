<?php

namespace Drupal\Core\Extension;

use Drupal\Core\Cache\CacheClearerInterface;

/**
 * A cache clearer for modules.
 */
class ModuleCacheClearer implements CacheClearerInterface {

  /**
   * Creates a new ModuleCacheClearer.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    protected ModuleExtensionList $moduleExtensionList,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function clearCache(): void {
    $this->moduleExtensionList->reset();
    $this->moduleHandler->invokeAll('rebuild');
  }

}
