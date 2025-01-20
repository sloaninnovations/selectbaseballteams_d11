<?php

namespace Drupal\image;

use Drupal\Core\Image\ImageResizePolicy;
use Drupal\field\FieldConfigInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a BC layer for modules providing old configurations.
 *
 * @internal
 */
class ImageConfigUpdater implements ContainerInjectionInterface {

  /**
   * Flag determining whether deprecations should be triggered.
   *
   * @var bool
   */
  protected $deprecationsEnabled = TRUE;

  /**
   * Stores which deprecations were triggered.
   *
   * @var bool
   */
  protected $triggeredDeprecations = [];

  /**
   * ImageConfigUpdater constructor.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static();
  }

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
   * Performs the required update.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field to update.
   *
   * @return bool
   *   Whether the field was updated.
   */
  public function updateField(FieldConfigInterface $field): bool {
    $changed = FALSE;
    if ($this->needsEntitySettingUpdate($field)) {
      $field->setSetting('resize_policy', ImageResizePolicy::ResizeLargerImages->value);
      $changed = TRUE;
    }
    return $changed;
  }

  /**
   * Checks if the field still misses 'resize_policy' setting.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field to update.
   *
   * @return bool
   *   TRUE if the field has not the new setting.
   */
  public function needsEntitySettingUpdate(FieldConfigInterface $field): bool {
    $needs_update = FALSE;
    if ($field->getType() === 'image' && $field->getSetting('resize_policy') === NULL) {
      $needs_update = TRUE;

      $deprecations_triggered = &$this->triggeredDeprecations['3325551'][$field->id()];
      if ($this->deprecationsEnabled && !$deprecations_triggered) {
        $deprecations_triggered = TRUE;
        @trigger_error('Image fields without "resize_policy" setting is deprecated in drupal:11.1.0 and will be removed in drupal:12.0.0. This new setting default value is "resize_larger_images". Profile, module and theme provided configuration should be updated. See https://www.drupal.org/node/3467820', E_USER_DEPRECATED);
      }
    }

    return $needs_update;
  }

}
