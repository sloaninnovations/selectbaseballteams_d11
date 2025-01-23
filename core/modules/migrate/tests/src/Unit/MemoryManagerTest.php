<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit;

use Drupal\migrate\MemoryManager;
use Drupal\migrate\MigrateMessageInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Tests the memory manager.
 *
 * @group migrate
 */
class MemoryManagerTest extends MigrateTestCase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The mocked migrate message.
   *
   * @var \Drupal\migrate\MigrateMessageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $message;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->message = $this->createMock(MigrateMessageInterface::class);
    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
  }

  /**
   * Tests ensureMemory.
   *
   * @param float $reclaim_threshold
   *   The ratio of the memory limit which will trigger a failed reclaim.
   * @param float $memory_threshold
   *   The ratio of the memory limit at which an operation will be interrupted.
   * @param int $memory_limit
   *   The memory limit.
   * @param int $memory_usage
   *   The first memory usage value.
   * @param int $cleared_memory_usage
   *   The fake amount of memory usage reported after memory reclaim.
   * @param bool $expected_ensure_memory
   *   Indicates that the desired memory threshold will be exceeded after
   *   reclamation.
   *
   * @dataProvider providerTestEnsureMemory
   */
  public function testEnsureMemory(float $reclaim_threshold, float $memory_threshold, int $memory_limit, int $memory_usage, int $cleared_memory_usage, bool $expected_ensure_memory): void {
    $limit_exceeded = $memory_threshold && $memory_usage / $memory_limit > $memory_threshold;
    $this->eventDispatcher
      ->expects($this->exactly($limit_exceeded ? 2 : 0))
      ->method('dispatch');
    $memory_manager = new TestMemoryManager($this->eventDispatcher, $reclaim_threshold, $memory_threshold, $memory_limit);
    $memory_manager->setMemoryUsage($memory_usage, $cleared_memory_usage);
    $result = $memory_manager->ensureMemory();
    $this->assertEquals($expected_ensure_memory, $result);
  }

  /**
   * Provides data for testEnsureMemory.
   */
  public static function providerTestEnsureMemory(): array {
    return [
      // Tests memoryExceeded method when a new batch is needed.
      'MemoryExceededNewBatch' => [
        'reclaim_threshold' => 0.9,
        'memory_threshold' => 0.85,
        'memory_limit' => 10000000,
        'memory_usage' => 0,
        'cleared_memory_usage' => 0,
        'expected_ensure_memory' => TRUE,
      ],
      // Tests memoryExceeded method when enough is cleared.
      'testMemoryExceededClearedEnough' => [
        'reclaim_threshold' => 0.9,
        'memory_threshold' => 0.85,
        'memory_limit' => 10000000,
        'memory_usage' => 10000000,
        'cleared_memory_usage' => (int) (10000000 * 0.75),
        'expected_ensure_memory' => TRUE,
      ],
      // Tests memoryExceeded when memory usage is not exceeded.
      'testMemoryNotExceeded' => [
        'reclaim_threshold' => 0.9,
        'memory_threshold' => 0.85,
        'memory_limit' => 10000000,
        'memory_usage' => (int) floor(10000000 * 0.85) - 1,
        'cleared_memory_usage' => 0,
        'expected_ensure_memory' => TRUE,
      ],
      // Tests memoryExceeded method when not enough is cleared.
      'testMemoryExceededNotCleared' => [
        'reclaim_threshold' => 0.9,
        'memory_threshold' => 0.85,
        'memory_limit' => 10000000,
        'memory_usage' => 10000000,
        'cleared_memory_usage' => (int) (10000000 * .95),
        'expected_ensure_memory' => FALSE,
      ],
    ];
  }

  /**
   * Test getUsageInBytes.
   *
   * @param int|null $memory_usage
   *   The memory usage in bytes.
   * @param int|null $expected_usage
   *   The expected memory usage in bytes.
   *
   * @dataProvider providerTestGetUsageInBytes
   */
  public function testGetUsageInBytes($memory_usage, $expected_usage): void {
    $memory_manager = new TestMemoryManager($this->eventDispatcher, 0.9, 0.85, 1000000);
    $memory_manager->setMemoryUsage($memory_usage);
    $result = $memory_manager->getUsageInBytes();
    $this->assertEquals($expected_usage, $result);
  }

  /**
   * Provides data for testGetUsageInBytes.
   */
  public static function providerTestGetUsageInBytes(): array {
    return [
      'Null' => [
        'memory_usage' => 0,
        'expected_usage' => 0,
      ],
      'Integer' => [
        'memory_usage' => 12345,
        'expected_usage' => 12345,
      ],
    ];
  }

  /**
   * Tests isLimitExceeded.
   *
   * @param float $reclaim_threshold
   *   The ratio of the memory limit which will trigger a failed reclaim.
   * @param float $memory_threshold
   *   The ratio of the memory limit at which an operation will be interrupted.
   * @param int $memory_limit
   *   The memory limit.
   * @param int|null $memory_usage
   *   The memory usage in bytes.
   * @param float $multiplier
   *   A percentage of the threshold.
   * @param bool $memory_exceeded
   *   A boolean indicating if memory was exceeded.
   *
   * @dataProvider providerIsLimitExceeded
   */
  public function testIsLimitExceeded($reclaim_threshold, $memory_threshold, $memory_limit, $memory_usage, $multiplier, $memory_exceeded): void {
    $memory_manager = new TestMemoryManager($this->eventDispatcher, $reclaim_threshold, $memory_threshold, $memory_limit);
    $memory_manager->setMemoryUsage($memory_usage);
    $result = $memory_manager->isLimitExceeded($multiplier);
    $this->assertEquals($memory_exceeded, $result);
  }

  /**
   * Provides data for testIsLimitExceeded.
   */
  public static function providerIsLimitExceeded(): array {
    return [
      'Not exceeded' => [
        'reclaim_threshold' => 0.9,
        'memory_threshold' => 0.85,
        'memory_limit' => 1000000,
        'memory_usage' => (int) (1000000 * .85),
        'multiplier' => 1.0,
        'memory_exceeded' => FALSE,
      ],
      'exceeded' => [
        'reclaim_threshold' => 0.9,
        'memory_threshold' => 0.85,
        'memory_limit' => 1000000,
        'memory_usage' => 10000001,
        'multiplier' => 1.0,
        'memory_exceeded' => TRUE,
      ],
    ];
  }

}

/**
 * Defines the TestMemoryManager class.
 */
class TestMemoryManager extends MemoryManager {

  /**
   * The fake memory usage in bytes.
   *
   * @var int
   */
  protected int $memoryUsage;

  /**
   * The cleared memory usage.
   *
   * @var int
   */
  protected int $clearedMemoryUsage;

  /**
   * {@inheritdoc}
   */
  public function getUsageInBytes(): int {
    return $this->memoryUsage;
  }

  /**
   * {@inheritdoc}
   */
  public function isLimitExceeded(float $multiplier = 1.0): bool {
    return parent::isLimitExceeded($multiplier);
  }

  /**
   * Sets the fake memory usage.
   *
   * @param int $memory_usage
   *   The fake memory usage value.
   * @param int $cleared_memory_usage
   *   (optional) The fake cleared memory value.
   */
  public function setMemoryUsage(int $memory_usage, int $cleared_memory_usage = 0): void {
    $this->memoryUsage = $memory_usage;
    $this->clearedMemoryUsage = $cleared_memory_usage;
  }

  /**
   * {@inheritdoc}
   */
  protected function dispatchEvent(string $phase): void {
    parent::dispatchEvent($phase);
    if ($phase === self::PRE_RECLAIMED && $this->clearedMemoryUsage) {
      $this->memoryUsage = $this->clearedMemoryUsage;
    }
  }

}
