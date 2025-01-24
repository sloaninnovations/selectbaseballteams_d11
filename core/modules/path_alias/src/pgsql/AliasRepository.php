<?php

namespace Drupal\path_alias\pgsql;

use Drupal\path_alias\AliasRepository as BaseAliasRepository;

/**
 * PostgreSQL specific alias repository implementation.
 */
class AliasRepository extends BaseAliasRepository {

  /**
   * {@inheritdoc}
   */
  public function preloadPathAlias($preloaded, $langcode) {
    // Chunk the paths so we don't end up with a single query with hundreds of
    // conditions.
    $chunks = array_chunk($preloaded, 100, TRUE);
    $return = [];
    foreach ($chunks as $chunk) {
      $select = $this->getBaseQuery()
        ->fields('base_table', ['path', 'alias']);

      $conditions = $this->connection->condition('OR');
      foreach ($chunk as $preloaded_item) {
        $conditions->condition('base_table.path', $this->connection->escapeLike($preloaded_item), 'LIKE');
      }
      $select->condition($conditions);

      $this->addLanguageFallback($select, $langcode);

      // We order by ID ASC so that fetchAllKeyed() returns the most recently
      // created alias for each source. Subsequent queries using fetchField()
      // must use ID DESC to have the same effect.
      $select->orderBy('base_table.id', 'ASC');

      $return = array_merge($return, $select->execute()->fetchAllKeyed());
    }

    return $return;
  }

}
