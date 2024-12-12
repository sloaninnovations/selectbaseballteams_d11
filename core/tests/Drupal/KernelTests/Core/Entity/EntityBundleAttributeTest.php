<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\bundle_attribute_test\Entity\TestBundle;
use Drupal\bundle_attribute_test\Entity\TestBundleWithLabel;
use Drupal\bundle_attribute_test\Entity\UserBundle;
use Drupal\bundle_attribute_test\Entity\EntityTest\SubdirTestBundle;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests entity bundle attributes.
 *
 * @group Entity
 */
class EntityBundleAttributeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'user',
    'bundle_attribute_test',
  ];

  /**
   * Test bundle class defined with attributes.
   */
  public function testBundleClassAttribute(): void {
    $bundleInfo = $this->container->get('entity_type.bundle.info');

    entity_test_create_bundle('test_bundle', 'BCA Test Bundle');
    $entity = EntityTest::create(['type' => 'test_bundle']);
    $this->assertInstanceOf(TestBundle::class, $entity);

    $label = $bundleInfo->getBundleInfo('entity_test')['test_bundle']['label'];
    $this->assertEquals('BCA Test Bundle', $label);

    entity_test_create_bundle('test_bundle_with_label', 'BCA Test Bundle with Label');
    $entity = EntityTest::create(['type' => 'test_bundle_with_label']);
    $this->assertInstanceOf(TestBundleWithLabel::class, $entity);

    $label = $bundleInfo->getBundleInfo('entity_test')['test_bundle_with_label']['label'];
    $this->assertEquals('Overridden label', $label);

    entity_test_create_bundle('subdir_test_bundle', 'BCA Subdir Test Bundle');
    $entity = EntityTest::create(['type' => 'subdir_test_bundle']);
    $this->assertInstanceOf(SubdirTestBundle::class, $entity);

    $user = User::create();
    $this->assertInstanceOf(UserBundle::class, $user);
  }

}
