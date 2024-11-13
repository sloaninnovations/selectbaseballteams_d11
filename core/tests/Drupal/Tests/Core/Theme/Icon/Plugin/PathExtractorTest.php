<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Icon\Plugin;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Theme\Icon\IconFinder;
use Drupal\Core\Theme\Plugin\IconExtractor\PathExtractor;
use Drupal\Tests\Core\Theme\Icon\IconTestTrait;

/**
 * @coversDefaultClass \Drupal\Core\Theme\Plugin\IconExtractor\PathExtractor
 *
 * @group icon
 */
class PathExtractorTest extends UnitTestCase {

  use IconTestTrait;

  /**
   * This test plugin id (icon pack id).
   */
  private string $pluginId = 'test_path';

  /**
   * The PathExtractor instance.
   *
   * @var \Drupal\Core\Theme\Plugin\IconExtractor\PathExtractor
   */
  private PathExtractor $pathExtractorPlugin;

  /**
   * The IconFinder instance.
   *
   * @var \Drupal\Core\Theme\Icon\IconFinder|\PHPUnit\Framework\MockObject\MockObject
   */
  private IconFinder $iconFinder;

  /**
   * {@inheritdoc}
   */
  public function setUp():void {
    parent::setUp();
    $this->iconFinder = $this->createMock(IconFinder::class);
    $this->pathExtractorPlugin = new PathExtractor(
      [
        'id' => $this->pluginId,
        'config' => ['sources' => ['foo/bar/baz.svg']],
        'template' => '_foo_',
        'relative_path' => 'modules/my_module',

      ],
      $this->pluginId,
      [],
      $this->iconFinder,
    );
  }

  /**
   * Data provider for ::testDiscoverIconsPath().
   *
   * @return \Generator
   *   The test cases, icons data with expected result.
   */
  public static function providerDiscoverIconsPath(): iterable {
    yield 'empty files' => [
      [],
      FALSE,
    ];

    yield 'single file' => [
      [
        [
          'icon_id' => 'foo',
          'source' => 'source/foo',
        ],
      ],
    ];

    yield 'multiple files with group' => [
      [
        [
          'icon_id' => 'foo',
          'source' => 'source/foo',
          'group' => 'baz',
        ],
        [
          'icon_id' => 'bar',
          'source' => 'source/bar',
          'group' => 'baz',
        ],
        [
          'icon_id' => 'baz',
          'source' => 'source/baz',
          'group' => 'qux',
        ],
      ],
    ];
  }

  /**
   * Test the PathExtractor::discoverIcons() method.
   *
   * @param array<array<string, string>> $icons
   *   The icons to test.
   * @param bool $expected
   *   Has icon result, default TRUE.
   *
   * @dataProvider providerDiscoverIconsPath
   */
  public function testDiscoverIconsPath(array $icons, bool $expected = TRUE): void {
    $return_list = [];
    foreach ($icons as $icon) {
      $return_list[] = $this->createIconData($icon);
    }
    $this->iconFinder->method('getFilesFromSources')->willReturn($return_list);

    $result = $this->pathExtractorPlugin->discoverIcons();
    if (FALSE === $expected) {
      $this->assertEmpty($result);
      return;
    }

    foreach ($result as $index => $icon) {
      $this->assertSame($this->pluginId . ':' . $icons[$index]['icon_id'], $icon->getId());
      $this->assertSame($icons[$index]['source'], $icon->getSource());
      $this->assertSame($icons[$index]['group'] ?? NULL, $icon->getGroup());
    }
  }

}
