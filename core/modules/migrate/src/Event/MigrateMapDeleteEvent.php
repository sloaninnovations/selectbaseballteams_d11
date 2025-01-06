<?php

namespace Drupal\migrate\Event;

use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\Component\EventDispatcher\Event;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Wraps a migrate map delete event for event listeners.
 */
class MigrateMapDeleteEvent extends Event {

  /**
   * Map plugin.
   *
   * @var \Drupal\migrate\Plugin\MigrateIdMapInterface
   */
  protected $map;

  /**
   * Array of source ID fields.
   *
   * @var array
   */
  protected $sourceId;

  /**
   * Migration entity.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * Constructs a migration map delete event object.
   *
   * @param \Drupal\migrate\Plugin\MigrateIdMapInterface $map
   *   Map plugin.
   * @param array $source_id
   *   Array of source ID fields representing the object being deleted from the map.
   * @param \Drupal\migrate\Plugin\MigrationInterface|null $migration
   *   Migration entity.
   */    
  public function __construct(MigrateIdMapInterface $map, array $source_id, ?MigrationInterface $migration = NULL) {
    $this->map = $map;
    $this->sourceId = $source_id;
    $this->migration = $migration;
  }

  /**
   * Gets the map plugin.
   *
   * @return \Drupal\migrate\Plugin\MigrateIdMapInterface
   *   The map plugin that caused the event to fire.
   */
  public function getMap() {
    return $this->map;
  }

  /**
   * Gets the source ID of the item being removed from the map.
   *
   * @return array
   *   Array of source ID fields.
   */
  public function getSourceId() {
    return $this->sourceId;
  }

  /**
   * Gets the migration entity.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   The migration entity involved.
   */
  public function getMigration() {
    return $this->migration;
  }

}
