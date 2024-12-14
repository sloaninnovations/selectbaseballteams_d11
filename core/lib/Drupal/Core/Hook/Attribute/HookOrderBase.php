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
   * The module of the hook.
   *
   * The module of the hook. Note this might be different from the module the
   * function is in.
   *
   * @internal
   */
  public string $module;

  /**
   * Constructs a HookOrderBase class.
   *
   * @param bool $shouldBeLarger
   *   Determines whether the hook should increase or decrease priority.
   */
  public function __construct(public readonly bool $shouldBeLarger) {}

  /**
   * {@inheritdoc}
   */
  public function set(Hook $hook, string $class): static {
    $this->hook = $hook->hook;
    $this->class = $class;
    $this->method = $hook->method;
    $this->module = $hook->module;
    return $this;
  }

}
