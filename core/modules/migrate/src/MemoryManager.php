<?php

namespace Drupal\migrate;

use Drupal\Component\Utility\Bytes;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateMemoryLimitEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * The memory manager for migrations.
 */
class MemoryManager implements MemoryManagerInterface {

  /**
   * The PHP memory limit expressed in bytes.
   *
   * @var int
   */
  protected int $memoryLimit;

  /**
   * MemoryManager constructor.
   *
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param float $memoryReclaimThreshold
   *   The ratio of the memory limit which will trigger a failed reclaim.
   * @param float $memoryThreshold
   *   The ratio of the memory limit at which an operation will be interrupted.
   * @param int|string|null $memory_limit
   *   The memory limit, either an integer or string size expressed as a number
   *   of bytes with optional SI or IEC binary unit prefix (e.g. 2, 3K, 5MB,
   *   10G, 6GiB, 8 bytes, 9mbytes).
   */
  public function __construct(
    protected EventDispatcherInterface $dispatcher,
    protected float $memoryReclaimThreshold,
    protected float $memoryThreshold,
    mixed $memory_limit = NULL,
  ) {
    if ($memory_limit === NULL) {
      // Auto detect memory limit.
      $memory_limit = trim(ini_get('memory_limit'));
    }
    // Record the memory limit in bytes.
    $this->memoryLimit = ($memory_limit === -1)
      ? PHP_INT_MAX
      : (int) Bytes::toNumber($memory_limit);
  }

  /**
   * {@inheritdoc}
   */
  public function ensureMemory(): bool {
    if (!$this->isLimitExceeded()) {
      return TRUE;
    }
    $this->dispatchEvent(self::PRE_RECLAIMED);
    // Re-check the reclaim threshold to ensure we reclaimed enough to
    // continue.
    $limit_exceeded = $this->isLimitExceeded($this->memoryReclaimThreshold);
    $this->dispatchEvent($limit_exceeded ? self::STILL_EXCEEDED : self::REDUCED_ENOUGH_TO_CONTINUE);
    return !$limit_exceeded;
  }

  /**
   * Dispatches a memory limit migrate event.
   *
   * @param string $phase
   *   The memory phase.
   */
  protected function dispatchEvent(string $phase): void {
    $event = new MigrateMemoryLimitEvent($this->getUsageRatio(), $this->getUsageInBytes(), $this->getLimit(), $phase);
    $this->dispatcher->dispatch($event, MigrateEvents::MEMORY_LIMIT);
  }

  /**
   * Returns the memory usage limit in bytes.
   *
   * @return int
   *   The memory usage limit in bytes.
   */
  protected function getLimit(): int {
    return $this->memoryLimit;
  }

  /**
   * Returns the memory usage so far.
   *
   * @return int
   *   The memory usage.
   */
  protected function getUsageInBytes(): int {
    return memory_get_usage();
  }

  /**
   * Returns the amount of memory used as a percentage.
   *
   * @return float|int
   *   The amount of memory used as a percentage.
   */
  protected function getUsageRatio(): float|int {
    return $this->getUsageInBytes() / $this->getLimit();
  }

  /**
   * Checks if the memory limit has been exceeded.
   *
   * @param float $multiplier
   *   (optional) A percentage of the threshold. If given, we will consider the
   *   memory limit exceeded if the current memory usage is over this percentage
   *   of the threshold. Defaults to 1 (i.e., the threshold is unchanged).
   *
   * @return bool
   *   TRUE if the memory limit is exceeded, otherwise FALSE.
   */
  protected function isLimitExceeded(float $multiplier = 1.0): bool {
    if (!$this->memoryThreshold) {
      return FALSE;
    }
    return $this->getUsageRatio() > ($multiplier * $this->memoryThreshold);
  }

}
