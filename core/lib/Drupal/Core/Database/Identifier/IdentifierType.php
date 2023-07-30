<?php

/**
 * @file
 * Enum for database identifier types.
 */

declare(strict_types=1);

namespace Drupal\Core\Database\Identifier;

/**
 * Enum for database identifier types.
 */
enum IdentifierType: int {
  case Generic = 0x0;
  case Database = 0x4;
  case Sequence = 0x5;
  case Table = 0x7;
  case PrefixedTable = 0x8;
  case Column = 0xC;
  case Index = 0xD;

  case Alias = 0x100;
}
