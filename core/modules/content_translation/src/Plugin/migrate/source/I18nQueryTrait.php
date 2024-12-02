<?php

namespace Drupal\content_translation\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\I18nQueryTrait as MigrateDrupalI18nQueryTrait;

/**
 * Gets an i18n translation from the source database.
 */
trait I18nQueryTrait {

  use MigrateDrupalI18nQueryTrait;

}
