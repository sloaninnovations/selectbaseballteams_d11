<?php

namespace Drupal\datetime_range;

/**
 * Declares constants used in the datetime range module.
 *
 * @deprecated in drupal:11.0.0 and is removed from drupal:11.1.0. Use
 *    enum DateTimeRangeDisplayOptions instead.
 * @see https://www.drupal.org/project/drupal/issues/3425141
 */
interface DateTimeRangeConstantsInterface {

  /**
   * Values for the 'from_to' formatter setting.
   */
  const BOTH = 'both';
  const START_DATE = 'start_date';
  const END_DATE = 'end_date';

}
