<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel\Plugin\ConfigAction;

use Drupal\Core\Config\Action\ConfigActionManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage;
use Drupal\layout_builder\SectionStorage\SectionStorageDefinition;

/**
 * @coversDefaultClass \Drupal\layout_builder\Plugin\ConfigAction\AddComponent
 *
 * @group layout_builder
 */
class AddComponentTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_discovery',
    'layout_builder',
    'layout_builder_defaults_test',
    'entity_test',
    'field',
    'system',
    'user',
  ];

  /**
   * The plugin.
   *
   * @var \Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage
   */
  protected $plugin;

  private readonly ConfigActionManager $configActionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    entity_test_create_bundle('bundle_with_extra_fields');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installConfig(['layout_builder_defaults_test']);

    $definition = (new SectionStorageDefinition())
      ->addContextDefinition('display', EntityContextDefinition::fromEntityTypeId('entity_view_display'))
      ->addContextDefinition('view_mode', new ContextDefinition('string'));
    $this->plugin = DefaultsSectionStorage::create($this->container, [], 'defaults', $definition);
    $this->configActionManager = $this->container->get('plugin.manager.config_action');
  }

  /**
   * Tests adding a component to a view display using a config action.
   */
  public function testAddComponent(): void {
    $this->configActionManager->applyAction(
      'addComponent',
      'core.entity_view_display.entity_test.bundle_with_extra_fields.default',
      [
        'section' => 0,
        'position' => 4,
        'component' => [
          'region' => [
            'layout_test_plugin' => 'content',
            'layout_twocol_section' => 'second',
          ],
          'default_region' => 'content',
          'id' => 'my_plugin_id',
        ],
        'additional' => [
          'some_additional_value' => 'my_custom_value',
        ]
      ]);

    $view_display = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('entity_view_display')
      ->load('entity_test.bundle_with_extra_fields.default');
    $this->plugin->setContextValue('display', $view_display);
    $components = $this->plugin->getSection(0)->getComponents();
    $uuid = end($components)->getUuid();

    $this->assertCount(2, $components);
    // We keep existing component.
    $this->assertSame('extra_field_block:entity_test:bundle_with_extra_fields:display_extra_field', $components['1445597a-c674-431d-ac0a-277d99347a7f']->getPluginId());
    // New component is added
    $this->assertSame('my_plugin_id', $components[$uuid]->getPluginId());
    // There is a match for regions' defaults we asked for.
    $this->assertSame('second', $components[$uuid]->getRegion());
    // As there is no other block in that section's region, weight is 0 no matter
    // of the 4th position we asked for.
    $this->assertSame(0, $components[$uuid]->getWeight());
    $this->assertSame(['some_additional_value' => 'my_custom_value'], $components[$uuid]->get('additional'));
  }

  /**
   * Tests adding a component to first position using a config action.
   */
  public function testAddComponentAtFirstPosition(): void {
    $this->configActionManager->applyAction(
      'addComponent',
      'core.entity_view_display.entity_test.bundle_with_extra_fields.default',
      [
        'section' => 0,
        'position' => 0,
        'component' => [
          'region' => [
            'layout_test_plugin' => 'content',
            'layout_twocol_section' => 'first',
          ],
          'default_region' => 'content',
          'id' => 'my_plugin_id',
        ],
      ]);

    $view_display = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('entity_view_display')
      ->load('entity_test.bundle_with_extra_fields.default');
    $this->plugin->setContextValue('display', $view_display);
    $components = $this->plugin->getSection(0)->getComponents();
    $uuid = end($components)->getUuid();

    $this->assertCount(2, $components);
    // We keep existing component.
    $this->assertSame('extra_field_block:entity_test:bundle_with_extra_fields:display_extra_field', $components['1445597a-c674-431d-ac0a-277d99347a7f']->getPluginId());
    // New component is added
    $this->assertSame('my_plugin_id', $components[$uuid]->getPluginId());
    // There is a match for regions' defaults we asked for.
    $this->assertSame('first', $components[$uuid]->getRegion());
    // We put this component before the existing one, as position was 0.
    $this->assertSame(1, $components[$uuid]->getWeight());
    $this->assertSame(2, $components['1445597a-c674-431d-ac0a-277d99347a7f']->getWeight());
  }

  /**
   * Tests adding a component to last position using a config action.
   */
  public function testAddComponentAtSecondPosition(): void {
    $this->configActionManager->applyAction(
      'addComponent',
      'core.entity_view_display.entity_test.bundle_with_extra_fields.default',
      [
        'section' => 0,
        'position' => 1,
        'component' => [
          'region' => [
            'layout_test_plugin' => 'content',
            'layout_twocol_section' => 'first',
          ],
          'default_region' => 'content',
          'id' => 'my_plugin_id',
        ],
      ]);

    $view_display = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('entity_view_display')
      ->load('entity_test.bundle_with_extra_fields.default');
    $this->plugin->setContextValue('display', $view_display);
    $components = $this->plugin->getSection(0)->getComponents();
    $uuid = end($components)->getUuid();

    $this->assertCount(2, $components);
    // We keep existing component.
    $this->assertSame('extra_field_block:entity_test:bundle_with_extra_fields:display_extra_field', $components['1445597a-c674-431d-ac0a-277d99347a7f']->getPluginId());
    // New component is added
    $this->assertSame('my_plugin_id', $components[$uuid]->getPluginId());
    // There is a match for regions' defaults we asked for.
    $this->assertSame('first', $components[$uuid]->getRegion());
    // We put this component before the existing one, as position was 0.
    $this->assertSame(2, $components[$uuid]->getWeight());
    $this->assertSame(1, $components['1445597a-c674-431d-ac0a-277d99347a7f']->getWeight());
  }

  /**
   * Tests adding a component to a view display using a config action.
   */
  public function testAddComponentToLayoutWithNoRegionDefined(): void {
    $this->configActionManager->applyAction(
      'addComponent',
      'core.entity_view_display.entity_test.bundle_with_extra_fields.default',
      [
        'section' => 0,
        'position' => 4,
        'component' => [
          'region' => [
            'layout_test_plugin' => 'content',
          ],
          'default_region' => 'second',
          'id' => 'my_plugin_id',
        ],
      ]);

    $view_display = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('entity_view_display')
      ->load('entity_test.bundle_with_extra_fields.default');
    $this->plugin->setContextValue('display', $view_display);
    $components = $this->plugin->getSection(0)->getComponents();
    $uuid = end($components)->getUuid();

    $this->assertCount(2, $components);
    // We keep existing component.
    $this->assertSame('extra_field_block:entity_test:bundle_with_extra_fields:display_extra_field', $components['1445597a-c674-431d-ac0a-277d99347a7f']->getPluginId());
    // New component is added
    $this->assertSame('my_plugin_id', $components[$uuid]->getPluginId());
    // There isn't a match for regions' defaults we asked for, so default_region is used.
    $this->assertSame('second', $components[$uuid]->getRegion());
    // As there is no other block in that section's region, weight is 0 no matter
    // of the 4th position we asked for.
    $this->assertSame(0, $components[$uuid]->getWeight());
  }

}
