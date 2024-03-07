<?php

namespace Drupal\Core\Plugin;

use Drupal\Core\Cache\CacheClearerInterface;

class PluginCacheClearer implements CacheClearerInterface {

  public function __construct() {}

  public function clearCache(): void {

  }

}
