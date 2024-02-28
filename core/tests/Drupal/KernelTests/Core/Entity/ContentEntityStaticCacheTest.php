<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the entity static cache when used by content entities.
 *
 * @group Entity
 */
class ContentEntityStaticCacheTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'user'];

  /**
   * A non-revisionable entity type ID to test with.
   *
   * @var string
   */
  protected $nonRevEntityTypeId = 'entity_test_mul';

  /**
   * A revisionable entity type ID to test with.
   *
   * @var string
   */
  protected $revEntityTypeId = 'entity_test_mulrev';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The user entity is required, as the content entities we are testing with
    // have an entity reference field with the user entity type as as target.
    $this->installEntitySchema('user');
    $this->installEntitySchema($this->nonRevEntityTypeId);
    $this->installEntitySchema($this->revEntityTypeId);
  }

  /**
   * Tests the static cache when loading content entities.
   */
  public function testEntityLoad() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $entity_type_manager->getStorage($this->revEntityTypeId);

    $rev_entity_type = $entity_type_manager->getDefinition($this->revEntityTypeId);
    $this->assertTrue($rev_entity_type->isStaticallyCacheable());
    $this->assertTrue($rev_entity_type->isPersistentlyCacheable());
    $this->assertTrue($rev_entity_type->isRevisionable());

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $storage->create();
    $entity->save();

    $non_default_rev_id = $entity->getRevisionId();
    $entity->setNewRevision();
    $entity->save();

    // Tests the three static cache rules for entity loading:
    // 1. Loading an entity multiple times by its ID returns always the same
    //    entity object reference.
    // 2. Loading an entity by its ID and by its default revision ID returns
    //    always the same entity object reference.
    // 3. Loading an entity multiple times by its revision ID returns always the
    //    same entity object reference.
    $this->assertSame($storage->load($entity->id()), $storage->load($entity->id()));
    $this->assertSame($storage->load($entity->id()), $storage->loadRevision($entity->getRevisionId()));
    $this->assertSame($storage->loadRevision($non_default_rev_id), $storage->loadRevision($non_default_rev_id));

    // Test that after resetting the entity cache then different object
    // references will be returned.
    $entity = $storage->load($entity->id());
    $entity_default_revision = $storage->loadRevision($entity->getRevisionId());
    $entity_non_default_revision = $storage->loadRevision($non_default_rev_id);
    $storage->resetCache();
    $this->assertNotSame($entity, $storage->load($entity->id()));
    $this->assertNotSame($entity_default_revision, $storage->loadRevision($entity->getRevisionId()));
    $this->assertNotSame($entity_non_default_revision, $storage->loadRevision($non_default_rev_id));

    // Tests that the behavior for the three rules remains unchanged after
    // resetting the entity cache.
    $this->assertSame($storage->load($entity->id()), $storage->load($entity->id()));
    $this->assertSame($storage->load($entity->id()), $storage->loadRevision($entity->getRevisionId()));
    $this->assertSame($storage->loadRevision($non_default_rev_id), $storage->loadRevision($non_default_rev_id));

    // Tests that after when first loading the revision and then the entity the
    // references will still be the same.
    $storage->resetCache();
    $this->assertSame($storage->loadRevision($entity->getRevisionId()), $storage->load($entity->id()));
  }

  /**
   * Tests that on loading unchanged entity a new object reference is returned.
   */
  public function testLoadUnchanged() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    $non_rev_entity_type = $entity_type_manager->getDefinition($this->nonRevEntityTypeId);
    $this->assertTrue($non_rev_entity_type->isStaticallyCacheable());
    $this->assertTrue($non_rev_entity_type->isPersistentlyCacheable());
    $this->doTestLoadUnchanged($this->nonRevEntityTypeId);

    $rev_entity_type = $entity_type_manager->getDefinition($this->revEntityTypeId);
    $this->assertTrue($rev_entity_type->isStaticallyCacheable());
    $this->assertTrue($rev_entity_type->isPersistentlyCacheable());
    $this->assertTrue($rev_entity_type->isRevisionable());
    $this->doTestLoadUnchanged($this->revEntityTypeId);
  }

  /**
   * Helper method for ::testLoadUnchanged().
   *
   * For revisionable entities both the loadUnchanged and loadRevisionUnchanged
   * storage methods are tested and for non-revisionable entities only the
   * loadUnchanged storage method is tested.
   *
   * @param string $entity_type_id
   *   The entity type ID to test Storage::loadUnchanged() with.
   */
  protected function doTestLoadUnchanged($entity_type_id) {
    foreach ([FALSE, TRUE] as $invalidate_entity_cache) {
      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
      $entity_type_manager = $this->container->get('entity_type.manager');
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);
      /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
      $storage = $entity_type_manager->getStorage($entity_type_id);

      $entity = $storage->create();
      $entity->save();

      $entity = $storage->load($entity->id());
      // Invalidating the entity cache will lead to not retrieving the entity
      // from the persistent entity cache. This simulates e.g. a behavior where
      // in an entity insert hook a field config is created and saved and then
      // the cache tag "entity_field_info" will be invalidated leading to
      // invalidating the entities in the entity cache, which will prevent
      // loadUnchanged from retrieving the entity from the persistent cache,
      // which will test that the static entity cache has been reset properly,
      // otherwise if not then the same entity object reference will be
      // returned.
      if ($invalidate_entity_cache) {
        Cache::invalidateTags(['entity_field_info']);
      }
      $unchanged = $storage->loadUnchanged($entity->id());
      $message = $invalidate_entity_cache ? 'loadUnchanged returns a different entity object reference when the entity cache is invalidated before that.' : 'loadUnchanged returns a different entity object reference when the entity cache is not invalidated before that.';
      $this->assertNotSame($entity, $unchanged, $message);
      // For entities, the static cache will be cleared by loadUnchanged() for
      // backwards-compatibility.
      $this->assertNotSame($entity, $storage->load($entity->id()));

      // For revisionable entities test the same way the
      // Storage::loadRevisionUnchanged method as well.
      if ($entity_type->isRevisionable()) {
        $entity = $storage->loadRevision($entity->getRevisionId());
        if ($invalidate_entity_cache) {
          Cache::invalidateTags(['entity_field_info']);
        }
        $unchanged = $storage->loadRevisionUnchanged($entity->getRevisionId());
        $message = $invalidate_entity_cache ? 'loadRevisionUnchanged returns a different entity object reference when the entity cache is invalidated before that.' : 'loadRevisionUnchanged returns a different entity object reference when the entity cache is not invalidated before that.';
        $this->assertNotSame($entity, $unchanged, $message);
        // Make sure that calling loadRevisionUnchanged() does not clear the
        // static revision cache.
        $this->assertSame($entity, $storage->loadRevision($entity->getRevisionId()));
      }
    }
  }

  /**
   * Tests loading a cached revision after a non-rev field has been changed.
   */
  public function testCacheNonRevField() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $entity_type_manager->getStorage($this->revEntityTypeId);

    $rev_entity_type = $entity_type_manager->getDefinition($this->revEntityTypeId);
    $this->assertTrue($rev_entity_type->isStaticallyCacheable());
    $this->assertTrue($rev_entity_type->isPersistentlyCacheable());
    $this->assertTrue($rev_entity_type->isRevisionable());

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $storage->create();
    $entity->set('non_rev_field', 'a');
    $entity->save();
    $non_default_first_rev_id = $entity->getRevisionId();

    $entity->set('non_rev_field', 'b');
    $entity->setNewRevision();
    $entity->save();
    $non_default_second_rev_id = $entity->getRevisionId();

    // Load the entity by the revision ID so that it gets cached into the
    // persistent cache.
    $storage->loadRevision($non_default_second_rev_id);

    // Create a new revision based on the first one, which will leave the second
    // revision in the persistent cache - i.e. when saving a revision only the
    // revision it originates from will be deleted from the persistent cache.
    $entity = $storage->loadRevision($non_default_first_rev_id);
    $entity->set('non_rev_field', 'c');
    $entity->setNewRevision();
    // Fields in the base table are updated only when saving a default revision.
    // As we've picked up an old revision we have to explicitly declare it as
    // default before saving it.
    $entity->isDefaultRevision(TRUE);
    $entity->save();
    $default_rev_id = $entity->getRevisionId();

    // Ensure that the middle non-default revision will contain the latest
    // value of the non-revisionable field.
    $entity = $storage->loadRevision($non_default_second_rev_id);
    $this->assertEquals('c', $entity->get('non_rev_field')->value);

    // Ensure that any other revisions contain the latest value of the
    // non-revisionable field.
    $entity = $storage->loadRevision($non_default_first_rev_id);
    $this->assertEquals('c', $entity->get('non_rev_field')->value);

    $entity = $storage->loadRevision($default_rev_id);
    $this->assertEquals('c', $entity->get('non_rev_field')->value);

    $entity = $storage->load($entity->id());
    $this->assertEquals('c', $entity->get('non_rev_field')->value);
  }

  /**
   * Tests deleting an entity or an entity revision.
   */
  public function testDelete() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $entity_type_manager->getStorage($this->revEntityTypeId);

    $rev_entity_type = $entity_type_manager->getDefinition($this->revEntityTypeId);
    $this->assertTrue($rev_entity_type->isStaticallyCacheable());
    $this->assertTrue($rev_entity_type->isPersistentlyCacheable());
    $this->assertTrue($rev_entity_type->isRevisionable());

    // Create an entity with three revisions by ensuring that each of the
    // revisions remains in the persistent entity revision cache.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $storage->create();
    $entity->save();
    $first_rev_id = $entity->getRevisionId();
    $entity = $storage->loadRevision($first_rev_id);

    $entity->setNewRevision();
    $entity->save();
    $second_rev_id = $entity->getRevisionId();
    $entity = $storage->loadRevision($second_rev_id);

    $entity->setNewRevision();
    $entity->save();
    $third_rev_id = $entity->getRevisionId();

    // Delete the first revision and ensure that it cannot be loaded.
    $storage->deleteRevision($first_rev_id);
    $this->assertNull($storage->loadRevision($first_rev_id));

    // Delete the entity and ensure that no revision can be loaded.
    $entity->delete();
    $this->assertNull($storage->loadRevision($first_rev_id));
    $this->assertNull($storage->loadRevision($second_rev_id));
    $this->assertNull($storage->loadRevision($third_rev_id));
  }

  /**
   * Test swapping revisions in hook_entity_preload().
   */
  public function testNonDefaultRevision() {
    \Drupal::state()->set('enable_hook', TRUE);
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage($this->revEntityTypeId);
    $revision_ids = [];
    // Create a revisionable entity and save it. We now have entity with ID 1
    // and single revision with revision ID 1, which is default one.
    $entity = EntityTestMulRev::create(['name' => 'Old name']);
    $entity->save();
    $default_revision_id = $entity->getRevisionId();
    $revision_ids[] = $default_revision_id;

    // Load the created entity and create a new revision.
    $loaded = EntityTestMulRev::load($entity->id());
    $loaded->setName('New name');
    // Following two lines simulate what workspaces do on saving entities in
    // entityPresave for non-default workspaces. All revisions there are set to
    // non-default. See comments to
    // Drupal\workspaces\EntityOperations::entityPresave().
    // This creates a revision with revision ID 2 for entity with ID 1.
    $loaded->setNewRevision(TRUE);
    $loaded->isDefaultRevision(FALSE);
    $loaded->save();
    $expected_revision_id = $loaded->getRevisionId();
    $revision_ids[] = $expected_revision_id;

    // After loading revisions, default revision will be in static cache for
    // entity. But on entity load, it is swapped in hook_entity_preload().
    $storage->loadMultipleRevisions($revision_ids);
    // Ensure that the preloaded entities are not influencing the default
    // static entity cache.
    \Drupal::state()->set($this->revEntityTypeId . '.preload_entities', [$entity->id() => $loaded->getRevisionId()]);
    $loaded = EntityTestMulRev::load($entity->id());
    $revision = $storage->loadRevision($loaded->getRevisionId());
    $this->assertEquals($loaded->getRevisionId(), $expected_revision_id);
    $this->assertEquals($revision->getRevisionId(), $expected_revision_id);
    $this->assertNotSame($revision, $loaded);
  }

}
