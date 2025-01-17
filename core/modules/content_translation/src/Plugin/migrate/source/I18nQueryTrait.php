<?php

namespace Drupal\content_translation\Plugin\migrate\source;

@trigger_error('The ' . __NAMESPACE__ . '\I18nQueryTrait is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use \Drupal\migrate_drupal\Plugin\migrate\source\I18nQueryTrait instead. See https://www.drupal.org/node/3439256', E_USER_DEPRECATED);

use Drupal\migrate_drupal\Plugin\migrate\source\I18nQueryTrait as MigrateDrupalI18nQueryTrait;

/**
 * Gets an i18n translation from the source database.
 */
trait I18nQueryTrait {

  use MigrateDrupalI18nQueryTrait;

}
