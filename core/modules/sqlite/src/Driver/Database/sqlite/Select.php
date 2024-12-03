<?php

namespace Drupal\sqlite\Driver\Database\sqlite;

use Drupal\Core\Database\Query\Select as QuerySelect;

/**
 * SQLite implementation of \Drupal\Core\Database\Query\Select.
 */
class Select extends QuerySelect {

  /**
   * Disables FOR UPDATE for SQLite.
   *
   * @param bool $set
   *   Whether to set or unset FOR UPDATE. Defaults to TRUE.
   *
   * @return $this
   *   The current query object.
   */
  public function forUpdate($set = TRUE) {
    // SQLite does not support FOR UPDATE so nothing to do.
    return $this;
  }

}
