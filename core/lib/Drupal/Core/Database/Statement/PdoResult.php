<?php

declare(strict_types=1);

namespace Drupal\Core\Database\Statement;

use Drupal\Core\Database\FetchModeTrait;

class PdoResult extends DqlResultBase {

  use FetchModeTrait;
  use PdoTrait;

  public function __construct(
    FetchAs $fetchMode,
    array $fetchOptions,
    protected readonly \PDOStatement $clientStatement,
  ) {
    parent::__construct($fetchMode, $fetchOptions);
  }

  /**
   * {@inheritdoc}
   */
  public function rowCount(): ?int {
    return $this->clientRowCount();
  }

  /**
   * {@inheritdoc}
   */
  public function setFetchMode(FetchAs $mode, array $fetchOptions = []): bool {
    return match ($mode) {
      FetchAs::ClassObject => $this->clientSetFetchMode($mode, $fetchOptions['class'], $fetchOptions['constructor_args'] ?? NULL),
      FetchAs::Column => $this->clientSetFetchMode($mode, $fetchOptions['column']),
      default => $this->clientSetFetchMode($mode),
    };
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FetchAs $mode, array $fetchOptions = []): array|object|int|float|string|bool|NULL {
    // @todo setFetchMode is temporary.
    $this->setFetchMode($mode, $fetchOptions);
    return $this->clientFetch($mode);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll(FetchAs $mode, array $fetchOptions): array {
    return $this->clientFetchAll($mode, $fetchOptions['column'] ?? $fetchOptions['class'] ?? NULL, $fetchOptions['constructor_args'] ?? NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllKeyed(int $keyIndex = 0, int $valueIndex = 1): array {
    $result = [];
    while ($record = $this->fetch(FetchAs::List, [])) {
      $result[$record[$keyIndex]] = $record[$valueIndex];
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllAssoc(string $column, FetchAs $mode, array $fetchOptions): array {
    $result = [];
    while ($rowAssoc = $this->fetch(FetchAs::Associative, $fetchOptions)) {
      $result[$rowAssoc[$column]] = $this->assocToFetchMode($rowAssoc, $mode, $fetchOptions);
    }
    return $result;
  }

}
