<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionStorage\SectionStorageDefinition;
use Drupal\layout_builder_test\Plugin\SectionStorage\SimpleConfigSectionStorage;

/**
 * Tests the test implementation of section storage.
 *
 * @coversDefaultClass \Drupal\layout_builder_test\Plugin\SectionStorage\SimpleConfigSectionStorage
 *
 * @group layout_builder
 */
class SimpleConfigSectionListTest extends SectionListTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getSectionList(array $section_data) {
    $config = $this->container->get('config.factory')->getEditable('layout_builder_test.test_simple_config.foobar');
    $section_data = array_map(function (Section $section) {
      return $section->toArray();
    }, $section_data);
    $config->set('sections', $section_data)->save();

    $definition = new SectionStorageDefinition(['id' => 'test_simple_config']);
    $plugin = SimpleConfigSectionStorage::create($this->container, [], 'test_simple_config', $definition);
    $plugin->setContext('config_id', new Context(new ContextDefinition('string'), 'foobar'));
    return $plugin;
  }

  /**
   * Tests adding a new component (as in config actions).
   *
   * @return void
   */
  public function testAddComponent(): void {
    $this->assertCount(1, $this->sectionList->getSection(0)->getComponents());
    $this->sectionList->addComponent(0, 1, [
      'uuid' => '30000000-0000-1000-a000-000000000000',
      'region' => [
        'layout_test_plugin' => 'content',
        'layout_2' => 'region_2',
      ],
      'default_region' => 'content',
      'id' => 'my_plugin_id',
    ]);
    $this->assertCount(2, $this->sectionList->getSection(0)->getComponents());
    $this->assertSame('content', $this->sectionList
      ->getSection(0)->getComponent('30000000-0000-1000-a000-000000000000')->getRegion());
    $this->assertSame('my_plugin_id', $this->sectionList
      ->getSection(0)->getComponent('30000000-0000-1000-a000-000000000000')->getPluginId());
  }

}
