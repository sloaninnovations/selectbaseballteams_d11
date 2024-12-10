<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

interface HookOrderInterface {

  public function setHook(string $hook): static;
  public function setClass(string $class): static;
  public function setMethod(string $method): static;

}
