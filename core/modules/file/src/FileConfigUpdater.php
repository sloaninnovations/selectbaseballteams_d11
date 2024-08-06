<?php

namespace Drupal\file;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Provides a BC layer for modules providing old configurations.
 *
 * @internal
 *   This class is only meant to fix outdated file configuration and its
 *   methods should not be invoked directly. It will be removed once all the
 *   associated updates have been removed.
 */
class FileConfigUpdater {

  /**
   * Flag determining whether deprecations should be triggered.
   *
   * @var bool
   */
  private $deprecationsEnabled = FALSE;

  /**
   * Stores which deprecations were triggered.
   *
   * @var bool
   */
  private $triggeredDeprecations = [];

  /**
   * Sets the deprecations enabling status.
   *
   * @param bool $enabled
   *   Whether deprecations should be enabled.
   */
  public function setDeprecationsEnabled(bool $enabled): void {
    $this->deprecationsEnabled = $enabled;
  }

  /**
   * Processes preload formatters.
   *
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display
   *   The view display.
   *
   * @return bool
   *   Whether the display was updated.
   */
  public function processPreloadSetting(EntityViewDisplayInterface $view_display): bool {
    $changed = FALSE;

    foreach ($view_display->getComponents() as $field => $component) {
      if (array_key_exists('type', $component)
        && in_array($component['type'], ['file_audio', 'file_video'])
        && !array_key_exists('preload', $component['settings'])) {
        $component['settings']['preload'] = 'metadata';
        $view_display->setComponent($field, $component);
        $changed = TRUE;
      }
    }

    return $changed;
  }

}
