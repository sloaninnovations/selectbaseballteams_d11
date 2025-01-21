<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Field;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the field storage create check subscriber.
 *
 * @group Field
 */
class FieldStorageCreateCheckTest extends KernelTestBase {

  /**
   * Modules to load.
   *
   * @var array
   */
  protected static $modules = ['entity_test', 'field'];

  /**
   * Tests the field storage create check subscriber.
   */
  public function testFieldStorageCreateCheck(): void {
    if (\Drupal::database()->driver() === 'mongodb') {
      $this->expectException(EntityStorageException::class);
      $this->expectExceptionMessage("Exception thrown while performing a schema update. Cannot add embedded table entity_test__field_test to the entity_test, because entity_test doesn't exist.");
    }
    else {
      $this->expectException(\LogicException::class);
      $this->expectExceptionMessage('Creating the "entity_test.field_test" field storage definition without the entity schema "entity_test" being installed is not allowed.');
    }

    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'integer',
    ])->save();
  }

}
