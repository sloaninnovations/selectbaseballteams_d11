<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Site\Settings;

/**
 * Defines a cache context for settings.
 *
 * Cache context ID: 'settings'.
 */
class SettingsCacheContext implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel(): string {
    return t('Settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(): string {
    $settings = Settings::getAll();
    return Crypt::hmacBase64(serialize($settings), Settings::getHashSalt());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(): CacheableMetadata {
    return new CacheableMetadata();
  }

}
