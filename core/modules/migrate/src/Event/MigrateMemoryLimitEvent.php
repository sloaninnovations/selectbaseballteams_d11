<?php

namespace Drupal\migrate\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Wraps a memory limit event for event listeners.
 */
final class MigrateMemoryLimitEvent extends Event {

  /**
   * Constructs a memory limit event object.
   *
   * @param float $usageRatio
   *   The memory usage ratio.
   * @param int $usageInBytes
   *   The memory usage in bytes.
   * @param int $limit
   *   The memory usage limit in bytes.
   * @param string $phase
   *   The phase of memory reclamation.
   */
  public function __construct(
    private float $usageRatio,
    private int $usageInBytes,
    private int $limit,
    private string $phase,
  ) {
  }

  /**
   * Gets the memory usage ratio.
   *
   * @return float
   *   The memory usage ratio.
   */
  public function getUsageRatio(): float {
    return $this->usageRatio;
  }

  /**
   * Gets the memory usage in bytes.
   *
   * @return int
   *   The memory usage in bytes.
   */
  public function getUsageInBytes(): int {
    return $this->usageInBytes;
  }

  /**
   * Gets the memory limit in bytes.
   *
   * @return int
   *   The memory limit in bytes.
   */
  public function getLimit(): int {
    return $this->limit;
  }

  /**
   * Gets the phase of memory reclamation.
   *
   * @return string
   *   The phase of memory reclamation.
   */
  public function getPhase(): string {
    return $this->phase;
  }

}
