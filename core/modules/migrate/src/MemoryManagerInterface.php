<?php

namespace Drupal\migrate;

/**
 * The memory manager for migrations.
 */
interface MemoryManagerInterface {

  /**
   * Pre memory reclaimed phase.
   */
  const PRE_RECLAIMED = 'pre reclaimed';

  /**
   * Memory is still exceeded phase.
   */
  const STILL_EXCEEDED = 'still exceeded';

  /**
   * Memory reduced phase.
   */
  const REDUCED_ENOUGH_TO_CONTINUE = 'reduced enough to continue';

  /**
   * Validates whether we've exceeded the desired memory threshold.
   *
   * If memory usage is too high, then dispatch a PRE_RECLAIMED MigrateEvent so
   * that event listeners can reclaim memory. Then dispatch a STILL_EXCEEDED
   * MigrateEvent or a REDUCED_ENOUGH_TO_CONTINUE MigrateEvent, depending on
   * whether enough memory was reclaimed.
   *
   * @return bool
   *   TRUE if memory usage is low enough, or if enough memory can be reclaimed.
   *   FALSE if memory usage is too high and not enough can be reclaimed.
   */
  public function ensureMemory(): bool;

}
