<?php

namespace Drupal\Core\Database;

/**
 * @todo
 */
class IdentifierHandler {

  const GENERIC = 0x0;
  const DATABASE = 0x4;
  const SEQUENCE = 0x5;
  const TABLE = 0x7;
  const PREFIXED_TABLE = 0x8;
  const COLUMN = 0xC;
  const INDEX = 0xD;
  const ALIAS = 0x10;

  /**
   * @todo
   */
  protected array $identifiers;

  /**
   * Constructs an IdentifierHandler object.
   *
   * @param string $tablePrefix
   *   The table prefix to be used by the database connection.
   * @param array{0:string, 1:string} $identifierQuotes
   *   The identifier quote characters for the database type. An array
   *   containing the start and end identifier quote characters for the
   *   database type. The ANSI SQL standard identifier quote character is a
   *   double quotation mark.
   */
  public function __construct(
    protected string $tablePrefix,
    protected array $identifierQuotes = ['"', '"'],
  ) {
  }

  /**
   * @todo
   */
  public function getTablePrefix(): string {
    return $this->tablePrefix;
  }

  /**
   * @todo
   */
  protected function setIdentifier(string $identifier, string $platform_identifier, int $type): void {
    $is_alias = (bool) ($type & static::ALIAS);
    $type = $type & 0xF;
    if (!$is_alias) {
      $this->identifiers['identifier'][$identifier][$type] = $platform_identifier;
      $this->identifiers['platform'][$platform_identifier][$type] = $identifier;
    }
    else {
      $this->identifiers['identifier'][$identifier][static::ALIAS][$type] = $platform_identifier;
      $this->identifiers['platform'][$platform_identifier][static::ALIAS][$type] = $identifier;
    }
  }

  /**
   * @todo
   */
  protected function hasIdentifier(string $identifier, int $type = 0): bool {
    return isset($this->identifiers['identifier'][$identifier][$type]);
  }

  /**
   * @todo
   */
  public function getPlatformIdentifierName(string $original_name, bool $quoted = TRUE): string {
    if (!$this->hasIdentifier($original_name, static::GENERIC)) {
      $this->setIdentifier($original_name, $this->resolvePlatformGenericIdentifier($original_name), static::GENERIC);
    }
    [$start_quote, $end_quote] = $this->identifierQuotes;
    $identifier = $this->identifiers['identifier'][$original_name][static::GENERIC];
    return $quoted ? $start_quote . $identifier . $end_quote : $identifier;
  }

  /**
   * @todo
   */
  public function getPlatformDatabaseName(string $original_name, bool $quoted = TRUE): string {
    $original_name = preg_replace('/[^A-Za-z0-9_]+/', '', $original_name);
    if (!$this->hasIdentifier($original_name, static::DATABASE)) {
      $this->setIdentifier($original_name, $this->resolvePlatformDatabaseIdentifier($original_name), static::DATABASE);
    }
    [$start_quote, $end_quote] = $this->identifierQuotes;
    return $quoted ?
      $start_quote . $this->identifiers['identifier'][$original_name][static::DATABASE] . $end_quote :
      $this->identifiers['identifier'][$original_name][static::DATABASE];
  }

  /**
   * @todo
   */
  public function getPlatformTableName(string $original_name, bool $prefixed = FALSE, bool $quoted = FALSE): string {
    $original_name = preg_replace('/[^A-Za-z0-9_.]+/', '', $original_name);
    if (!$this->hasIdentifier($original_name, static::TABLE)) {
      $table_name = $this->resolvePlatformTableIdentifier($original_name);
      $this->setIdentifier($original_name, $table_name, static::TABLE);
      $this->setIdentifier($original_name, $this->getTablePrefix() . $table_name, static::PREFIXED_TABLE);
    }
    [$start_quote, $end_quote] = $this->identifierQuotes;
    $table = $prefixed ? $this->identifiers['identifier'][$original_name][static::PREFIXED_TABLE] : $this->identifiers['identifier'][$original_name][static::TABLE];
    return $quoted ? $start_quote . str_replace(".", "$end_quote.$start_quote", $table) . $end_quote : $table;
  }

  /**
   * @todo
   */
  public function getPlatformColumnName(string $original_name, bool $quoted = TRUE): string {
    if ($original_name === '') {
      return '';
    }
    $original_name = preg_replace('/[^A-Za-z0-9_.]+/', '', $original_name);
    if (!$this->hasIdentifier($original_name, static::COLUMN)) {
      $this->setIdentifier($original_name, $this->resolvePlatformColumnIdentifier($original_name), static::COLUMN);
    }
    // Sometimes fields have the format table_alias.field. In such cases
    // both identifiers should be quoted, for example, "table_alias"."field".
    [$start_quote, $end_quote] = $this->identifierQuotes;
    return $quoted ?
      $start_quote . str_replace(".", "$end_quote.$start_quote", $this->identifiers['identifier'][$original_name][static::COLUMN]) . $end_quote :
      $this->identifiers['identifier'][$original_name][static::COLUMN];
  }

  /**
   * @todo
   */
  public function getPlatformAliasName(string $original_name, int $type = 0, bool $quoted = TRUE): string {
    $original_name = preg_replace('/[^A-Za-z0-9_]+/', '', $original_name);
    if ($original_name[0] === $this->identifierQuotes[0]) {
      $original_name = substr($original_name, 1, -1);
    }
    if (!$this->hasIdentifier($original_name, static::ALIAS)) {
      $this->setIdentifier($original_name, $this->resolvePlatformGenericIdentifier($original_name), $type | static::ALIAS);
    }
    [$start_quote, $end_quote] = $this->identifierQuotes;
    $alias = $this->identifiers['identifier'][$original_name][static::ALIAS][$type] ?? $this->identifiers['identifier'][$original_name][static::ALIAS][0];
    return $quoted ? $start_quote . $alias . $end_quote : $alias;
  }

  /**
   * @todo
   */
  protected function resolvePlatformGenericIdentifier(string $identifier): string {
    return $identifier;
  }

  /**
   * @todo
   */
  protected function resolvePlatformDatabaseIdentifier(string $identifier): string {
    return $identifier;
  }

  /**
   * @todo
   */
  protected function resolvePlatformTableIdentifier(string $identifier): string {
    return $identifier;
  }

  /**
   * @todo
   */
  protected function resolvePlatformColumnIdentifier(string $identifier): string {
    return $identifier;
  }

  /**
   * @todo
   */
  protected function resolvePlatformAliasIdentifier(string $identifier, int $type = 0): string {
    return $identifier;
  }

}
