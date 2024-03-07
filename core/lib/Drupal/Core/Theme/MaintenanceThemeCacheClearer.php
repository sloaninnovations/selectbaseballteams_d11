<?php

namespace Drupal\Core\Theme;

use Drupal\Core\Cache\CacheClearerInterface;

/**
 * A cache clearer for the maintenance theme.
 */
class MaintenanceThemeCacheClearer implements CacheClearerInterface {

  /**
   * Creates a MaintenanceThemeCacheClearer.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   */
  public function __construct(
    protected ThemeManagerInterface $themeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function clearCache(): void {
    // Re-initialize the maintenance theme, if the current request attempted to
    // use it. Unlike regular usages of this function, the installer and update
    // scripts need to flush all caches during GET requests/page building.
    if (function_exists('_drupal_maintenance_theme')) {
      $this->themeManager->resetActiveTheme();
      drupal_maintenance_theme();
    }
  }

}
