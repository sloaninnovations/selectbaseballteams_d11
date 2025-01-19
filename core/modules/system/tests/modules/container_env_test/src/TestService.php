<?php

declare(strict_types=1);

namespace Drupal\container_env_test;

class TestService {

  public function __construct(public string $parameter) {
  }

}
