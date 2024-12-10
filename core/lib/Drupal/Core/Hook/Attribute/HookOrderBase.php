<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

class HookOrderBase implements HookOrderInterface {

  public readonly string $hook;

  public readonly string $class;

  public readonly string $method;

  public function __construct(public readonly bool $shouldBeLarger) {

  }

  public function setHook(string $hook): static {
    $this->hook = $hook;
    return $this;
  }

  public function setClass(string $class): static {
    $this->class = $class;
    return $this;
  }

  public function setMethod(string $method): static {
    $this->method = $method;
    return $this;
  }

}
