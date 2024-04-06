<?php

namespace Drupal\content_translation\Plugin\migrate\source;

@trigger_error('\Drupal\content_translation\Plugin\migrate\source\I18nQueryTrait is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Instead, use \Drupal\migrate\Plugin\migrate\source\I18nQueryTrait. See https://www.drupal.org/node/3439256', E_USER_DEPRECATED);

use Drupal\migrate\Plugin\migrate\source\I18nQueryTrait as MigrateI18nQueryTrait;

/**
 * Gets an i18n translation from the source database.
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use
 *  \Drupal\migrate\Plugin\migrate\source\I18nQueryTrait.
 *
 * @see https://www.drupal.org/node/3439256
 */
trait I18nQueryTrait {
  use MigrateI18nQueryTrait;

}
