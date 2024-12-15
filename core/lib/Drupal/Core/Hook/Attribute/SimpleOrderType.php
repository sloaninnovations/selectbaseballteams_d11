<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

enum SimpleOrderType: int {

  case First = 1;
  case Last = 0;

}
