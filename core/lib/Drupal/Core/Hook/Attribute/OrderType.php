<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

enum OrderType {

  case Before;
  case After;

  public function shouldBeLast(): bool {
    return match($this) {
      OrderType::Before => TRUE,
      OrderType::After => FALSE,
    };
  }

}
