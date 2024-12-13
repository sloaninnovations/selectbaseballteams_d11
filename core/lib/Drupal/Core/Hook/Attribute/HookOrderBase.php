<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Common set of functionality needed by attributes that handle ordering hooks.
 */
class HookOrderBase implements HookOrderInterface {

  /**
   * The hook that should be ordered.
   *
   * @internal
   */
  public string $hook;

  /**
   * The class the hook is found in.
   *
   * @internal
   */
  public string $class;

  /**
   * The method of the hook.
   *
   * @internal
   */
  public string $method;

  /**
   * Constructs a HookOrderBase class.
   */
  public function __construct(public readonly bool $shouldBeLarger) {}

  /**
   * {@inheritdoc}
   */
  public function set(string $hook, string $class, string $method): static {
    $this->hook = $hook;
    $this->class = $class;
    $this->method = $method;
    return $this;
  }

}
