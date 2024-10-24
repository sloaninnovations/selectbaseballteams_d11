<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Icon;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Render\Element\Icon;
use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element\Icon
 *
 * @group icon
 */
class IconTest extends UnitTestCase {

  use IconTestTrait;

  /**
   * The container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  private ContainerBuilder $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);
  }

  /**
   * Test the Icon::getInfo method.
   */
  public function testGetInfo(): void {
    $icon = new Icon([], 'test', 'test');
    $info = $icon->getInfo();

    $this->assertArrayHasKey('#pre_render', $info);
    $this->assertArrayHasKey('#icon_pack', $info);
    $this->assertArrayHasKey('#icon', $info);
    $this->assertArrayHasKey('#settings', $info);

    $this->assertSame([['Drupal\Core\Render\Element\Icon', 'preRenderIcon']], $info['#pre_render']);
    $this->assertSame([], $info['#settings']);
  }

  /**
   * Data provider for ::testPreRenderIcon().
   *
   * @return \Generator
   *   Provide test data as:
   *   - array of information for the icon
   *   - result array of render element
   */
  public static function providerPreRenderIcon(): iterable {
    yield 'minimum icon definition' => [
      [
        'pack_id' => 'pack_id',
        'icon_id' => 'icon_id',
        'source' => '/foo/bar',
        'template' => 'my_template',
      ],
      [
        '#type' => 'inline_template',
        '#template' => 'my_template',
        '#context' => [
          'icon_id' => 'icon_id',
          'source' => '/foo/bar',
        ],
      ],
    ];

    yield 'full icon definition.' => [
      [
        'pack_id' => 'pack_id',
        'pack_label' => 'Baz',
        'icon_id' => 'icon_id',
        'source' => '/foo/bar',
        'group' => 'test_group',
        'content' => 'test_content',
        'template' => 'my_template',
        'library' => 'my_theme/my_library',
        'icon_settings' => ['foo' => 'bar'],
      ],
      [
        '#type' => 'inline_template',
        '#template' => 'my_template',
        '#attached' => ['library' => ['my_theme/my_library']],
        '#context' => [
          'icon_id' => 'icon_id',
          'source' => '/foo/bar',
          'content' => 'test_content',
          'foo' => 'bar',
        ],
      ],
    ];
  }

  /**
   * Test the Icon::preRenderIcon method.
   *
   * @param array $data
   *   The icon data.
   * @param array $expected
   *   The result expected.
   *
   * @dataProvider providerPreRenderIcon
   */
  public function testPreRenderIcon(array $data, array $expected): void {
    $icon = $this->createTestIcon($data);
    $icon_full_id = IconDefinition::createIconId($data['pack_id'], $data['icon_id']);

    $prophecy = $this->prophesize('\Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface');
    $prophecy->getIcon($icon_full_id)
      ->willReturn($icon);

    $pluginManagerIconPack = $prophecy->reveal();
    $this->container->set('plugin.manager.icon_pack', $pluginManagerIconPack);

    $element = [
      '#type' => 'icon',
      '#icon_pack' => $data['pack_id'],
      '#icon' => $data['icon_id'],
      '#settings' => $data['icon_settings'] ?? [],
    ];

    $actual = Icon::preRenderIcon($element);

    $this->assertEquals($expected, $actual['inline-template']);
  }

  /**
   * Test the Icon::preRenderIcon method.
   */
  public function testPreRenderIconEmptyValues(): void {
    $element = [
      '#type' => 'icon',
      '#icon_pack' => '',
      '#icon' => '',
    ];

    $prophecy = $this->prophesize('\Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface');
    $prophecy->getIcon(':')
      ->willReturn(NULL);

    $pluginManagerIconPack = $prophecy->reveal();
    $this->container->set('plugin.manager.icon_pack', $pluginManagerIconPack);

    $actual = Icon::preRenderIcon($element);

    $this->assertEquals($element, $actual);
  }

  /**
   * Test the Icon::preRenderIcon method.
   */
  public function testPreRenderIconNoIcon(): void {
    $prophecy = $this->prophesize('Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface');
    $prophecy->getIcon('foo:bar')->willReturn(NULL);

    $pluginManagerIconPack = $prophecy->reveal();
    $this->container->set('plugin.manager.icon_pack', $pluginManagerIconPack);

    $element = [
      '#type' => 'icon',
      '#icon_pack' => 'foo',
      '#icon' => 'bar',
    ];

    $actual = Icon::preRenderIcon($element);

    $this->assertEquals($element, $actual);
  }

}
