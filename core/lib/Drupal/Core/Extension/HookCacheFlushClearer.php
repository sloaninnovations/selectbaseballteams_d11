<?php

declare(strict_types=1);

namespace Drupal\Core\Extension;

use Drupal\Core\Cache\CacheClearerInterface;

/**
 * Cache clearer that invokes hook_cache_flush.
 */
class HookCacheFlushClearer implements CacheClearerInterface {

  /**
   * Creates a new HookCacheFlushClearer.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(protected ModuleHandlerInterface $moduleHandler) {}

  /**
   * {@inheritdoc}
   */
  public function clearCache(): void {
    $this->moduleHandler->invokeAll('cache_flush');
  }

}
