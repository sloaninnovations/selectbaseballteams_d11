<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\d7\BlockCustomTranslation;
use Drupal\migrate\Attribute\MigrateSource;

/**
 * Drupal 6 i18n content block translations source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 */
#[MigrateSource(
  id: 'd6_box_translation',
  source_module: 'i18nblocks',
)]
class BoxTranslation extends BlockCustomTranslation {

  /**
   * Drupal 6 table names.
   */
  const CUSTOM_BLOCK_TABLE = 'boxes';
  const I18N_STRING_TABLE = 'i18n_strings';

}
