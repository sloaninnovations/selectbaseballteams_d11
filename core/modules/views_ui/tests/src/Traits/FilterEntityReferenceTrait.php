<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Traits;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Sets up the entity types and relationships for entity reference tests.
 *
 * This trait is meant to be used only by test classes.
 */
trait FilterEntityReferenceTrait {

  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }
  use NodeCreationTrait {
    getNodeByTitle as drupalGetNodeByTitle;
    createNode as drupalCreateNode;
  }

  /**
   * The host Entity type to add the entity reference field to.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $hostEntityType;

  /**
   * The Entity type to be referenced by the host Entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $targetEntityType;

  /**
   * Entities to be used as reference targets.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $targetEntities;

  /**
   * Host entities which contain the reference fields to the target entities.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $hostEntities;

  /**
   * Sets up the entity types and relationships.
   */
  protected function setUpEntityTypes() {

    // Create an entity type, and a referenceable type. Since these are coded
    // into the test view, they are not randomly named.
    $this->hostEntityType = $this->drupalCreateContentType(['type' => 'page']);
    $this->targetEntityType = $this->drupalCreateContentType(['type' => 'article']);

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'node',
      ],
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test',
      'bundle' => $this->hostEntityType->id(),
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => [
            $this->targetEntityType->id() => $this->targetEntityType->label(),
          ],
        ],
      ],
    ]);
    $field->save();

    // Create 10 nodes for use as target entities.
    for ($i = 0; $i < 10; $i++) {
      $node = $this->drupalCreateNode([
        'type' => $this->targetEntityType->id(),
        'title' => ucfirst($this->targetEntityType->id()) . ' ' . $i,
      ]);
      $this->targetEntities[$node->id()] = $node;
    }

    // Create 1 host entity to reference target entities from.
    $node = $this->drupalCreateNode([
      'type' => $this->hostEntityType->id(),
      'title' => ucfirst($this->hostEntityType->id()) . ' 0',
    ]);
    $this->hostEntities = [
      $node->id() => $node,
    ];

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test_config',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'node_type',
      ],
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test_config',
      'bundle' => $this->hostEntityType->id(),
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          'sort' => ['field' => '_none'],
        ],
      ],
    ]);
    $field->save();
  }

}
