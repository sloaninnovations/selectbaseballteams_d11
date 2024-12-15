<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

enum OrderType: int {

  case Before = 1;
  case After = 0;

}
