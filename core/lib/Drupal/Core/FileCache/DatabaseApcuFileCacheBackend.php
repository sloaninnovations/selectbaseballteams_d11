<?php

declare(strict_types=1);

namespace Drupal\Core\FileCache;

use Drupal\Component\FileCache\FileCacheBackendInterface;
use Drupal\Component\FileCache\GarbageCollectionInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Site\Settings;

/**
 * Database and APCu backend for the file cache.
 */
class DatabaseApcuFileCacheBackend implements FileCacheBackendInterface, GarbageCollectionInterface {

  /**
   * The database table to use.
   */
  protected string $table = 'database_file_cache';

  /**
   * The time to live for cache items.
   */
  protected int $ttl;

  public function __construct() {
    // Set a default TTL to 90 days, this is to allow cache items for cache keys
    // that are no longer relevant to be garbage collected from the database.
    $this->ttl = Settings::get('file_cache_ttl', 86400 * 90);
  }

  /**
   * Gets a database connection.
   *
   * @return \Drupal\Core\Database\Connection|null
   *   The default database connection if it's available or FALSE.
   */
  public function getConnection(): ?Connection {
    if (Database::getConnectionInfo('default')) {
      return Database::getConnection('default', 'default');
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(array $cids) {
    $cache = apcu_fetch($cids);
    $remaining_cids = array_flip(array_diff_key(array_flip($cids), $cache));
    if (!$remaining_cids) {
      return $cache;
    }
    $cid_mapping = [];
    foreach ($remaining_cids as $cid) {
      $cid_mapping[$this->normalizeCid($cid)] = $cid;
    }
    $result = [];
    try {
      if ($connection = $this->getConnection()) {
        // On a cold cache, this can be called thousands of times, so avoid
        // using the query builder.
        $result = $connection->query('SELECT [cid], [data], [serialized], [created], [expire] FROM {' . $connection->escapeTable($this->table) . '}   WHERE [cid] IN ( :cids[] ) ORDER BY [cid]', [':cids[]' => array_keys($cid_mapping)]);
      }
    }
    catch (\Exception) {
      // Nothing to do.
    }
    $database_cache = [];
    foreach ($result as $item) {
      // Map the cache ID back to the original.
      $item->cid = $cid_mapping[$item->cid];
      if ($item && $item->expire >= time()) {
        $data = $item->serialized ? unserialize($item->data) : $item->data;
        $database_cache[$item->cid] = $data;
        $cache[$item->cid] = $data;
      }
    }
    // When items are found in the database but not APCu, write back to APCu.
    apcu_store($database_cache);
    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function store($cid, $data): void {
    // Write to apcu first so that cached items are immediately available to
    // other processes.
    apcu_store($cid, $data);
    $try_again = FALSE;
    try {
      // The bin might not yet exist.
      $this->doStore($cid, $data);
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the bins.
      if (!$try_again = $this->ensureBinExists()) {
        // If the exception happened for other reason than the missing bin
        // table, propagate the exception.
        throw $e;
      }
    }
    // Now that the bin has been created, try again if necessary.
    if ($try_again) {
      $this->doStore($cid, $data);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid): void {
    // Delete from the database first so that other processes don't try to write
    // a stale cache item from the database back to APCu.
    $database_cid = $this->normalizeCid($cid);
    try {
      if ($connection = $this->getConnection()) {
        $connection->delete($this->table)
          ->condition('cid', $database_cid)
          ->execute();
      }
    }
    catch (\Exception) {
      // Nothing to do.
    }

    apcu_delete($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection(): void {
    try {
      if ($connection = $this->getConnection()) {
        $connection->delete($this->table)
          ->condition('expire', time(), '<')
          ->execute();
      }
    }
    catch (\Exception) {
      // If the table does not exist, it surely does not have garbage in it.
      // If the table exists, the next garbage collection will clean up.
      // There is nothing to do.
    }
  }

  /**
   * Normalizes a cache ID in order to comply with database limitations.
   *
   * @param string $cid
   *   The passed in cache ID.
   *
   * @return string
   *   An ASCII-encoded cache ID that is at most 255 characters long.
   */
  private function normalizeCid(string $cid): string {
    // Nothing to do if the ID is a US ASCII string of 255 characters or less.
    // Additionally check for trailing spaces in the cache ID because MySQL
    // may or may not take these into account when making comparisons.
    // @see https://dev.mysql.com/doc/refman/9.0/en/char.html
    $cid_is_ascii = mb_check_encoding($cid, 'ASCII');
    if (strlen($cid) <= 255 && $cid_is_ascii && !str_ends_with($cid, ' ')) {
      return $cid;
    }
    // Return a string that uses as much as possible of the original cache ID
    // with the hash appended.
    $hash = Crypt::hashBase64($cid);
    if (!$cid_is_ascii) {
      return $hash;
    }
    return substr($cid, 0, 255 - strlen($hash)) . $hash;
  }

  /**
   * Writes an item to the database cache.
   *
   * @param string $cid
   *   The cache ID.
   * @param mixed $data
   *   The cache data to store.
   *
   * @return void
   */
  protected function doStore(string $cid, mixed $data): void {
    $fields = [
      'cid' => $this->normalizeCid($cid),
      'expire' => time() + $this->ttl,
      'created' => round(microtime(TRUE), 3),
    ];

    if (!is_string($data)) {
      $fields['data'] = serialize($data);
      $fields['serialized'] = 1;
    }
    else {
      $fields['data'] = $data;
      $fields['serialized'] = 0;
    }
    // Use an upsert query which is atomic and optimized for multiple-row
    // merges.
    if ($connection = $this->getConnection()) {
      $connection
        ->upsert($this->table)
        ->key('cid')
        ->fields(['cid', 'expire', 'created', 'data', 'serialized'])
        ->values($fields)
        ->execute();
    }
  }

  /**
   * Check if the cache bin exists and create it if not.
   *
   * @return bool
   *   TRUE if the table exists or was created, false if it could not be created.
   */
  private function ensureBinExists(): bool {
    try {
      $database_schema = $this->getConnection()->schema();
      if (!$database_schema->tableExists($this->table)) {
        $schema_definition = $this->schemaDefinition();
        $database_schema->createTable($this->table, $schema_definition);
        return TRUE;
      }
    }
    // If another process has already created the cache table, attempting to
    // recreate it will throw an exception. In this case just catch the
    // exception and do nothing.
    catch (DatabaseException) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Defines the schema for the {cache_*} bin tables.
   *
   * @internal
   *
   * @return array
   */
  public function schemaDefinition(): array {
    $schema = [
      'description' => 'Storage for the FileCache API.',
      'fields' => [
        'cid' => [
          'description' => 'Primary Key: Unique cache ID.',
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'binary' => TRUE,
        ],
        'data' => [
          'description' => 'A collection of data to cache.',
          'type' => 'blob',
          'not null' => FALSE,
          'size' => 'big',
        ],
        'expire' => [
          'description' => 'A Unix timestamp indicating when the cache entry should expire.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'big',
        ],
        'created' => [
          'description' => 'A timestamp with millisecond precision indicating when the cache entry was created.',
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 3,
          'not null' => TRUE,
          'default' => 0,
        ],
        'serialized' => [
          'description' => 'A flag to indicate whether content is serialized (1) or not (0).',
          'type' => 'int',
          'size' => 'small',
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
      'indexes' => [
        'expire' => ['expire'],
        'created' => ['created'],
      ],
      'primary key' => ['cid'],
    ];
    return $schema;
  }

}
