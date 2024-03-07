<?php

namespace Drupal\Core\Extension;

use Drupal\Core\Cache\CacheClearerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * A cache clearer for the theme.
 */
class ThemeCacheClearer implements CacheClearerInterface {

  /**
   * Creates a new ThemeCacheClearer.
   *
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme extension list.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   */
  public function __construct(
    protected ThemeExtensionList $themeExtensionList,
    protected ThemeHandlerInterface $themeHandler,
    protected ThemeManagerInterface $themeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function clearCache(): void {
    $this->themeExtensionList->reset();
    $this->themeHandler->refreshInfo();
    $this->themeManager->resetActiveTheme();
  }

}
