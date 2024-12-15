<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

enum SimpleOrderType {

  case First;
  case Last;

  public function shouldBeLast(): bool {
    return match($this) {
      SimpleOrderType::First => TRUE,
      SimpleOrderType::Last => FALSE,
    };
  }

}
