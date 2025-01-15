<?php

declare(strict_types=1);

namespace Drupal\Core\Database\Statement;

abstract class DqlResultBase {

  public function __construct(
    protected FetchAs $fetchMode,
    protected array $fetchOptions,
  ) {
  }

}
