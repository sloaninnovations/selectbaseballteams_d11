<?php

declare(strict_types=1);

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Attribute\Bundle;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Bundle class manager.
 */
class BundleClassManager extends DefaultPluginManager {

  /**
   * Constructs a new BundlePluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache, ModuleHandlerInterface $module_handler) {
    parent::__construct('Entity', $namespaces, $module_handler, EntityInterface::class, Bundle::class);
    $this->setCacheBackend($cache, 'bundle_class');
  }

}
