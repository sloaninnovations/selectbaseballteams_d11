<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Icon;

use Drupal\Core\Theme\Icon\IconDefinitionInterface;
use Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface;
use Drupal\Core\Template\IconsTwigExtension;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Core\Template\IconsTwigExtension
 *
 * @group icon
 */
class IconsTwigExtensionTest extends TestCase {

  /**
   * The plugin manager.
   *
   * @var \Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private IconPackManagerInterface $pluginManagerIconPack;

  /**
   * The twig extension.
   *
   * @var \Drupal\Core\Template\IconsTwigExtension
   */
  private IconsTwigExtension $iconsTwigExtension;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->pluginManagerIconPack = $this->createMock(IconPackManagerInterface::class);
    $this->iconsTwigExtension = new IconsTwigExtension($this->pluginManagerIconPack);
  }

  /**
   * Test the IconsTwigExtension::getFunctions method.
   */
  public function testGetFunctions(): void {
    $functions = $this->iconsTwigExtension->getFunctions();
    $this->assertCount(1, $functions);
    $this->assertEquals('icon', $functions[0]->getName());
  }

  /**
   * Test the IconsTwigExtension::getIconRenderable method.
   */
  public function testGetIconRenderableIconNotFound(): void {
    $this->pluginManagerIconPack
      ->method('getIcon')
      ->willReturn(NULL);

    $result = $this->iconsTwigExtension->getIconRenderable('pack_id', 'icon_id');
    $this->assertEmpty($result);
  }

  /**
   * Test the IconsTwigExtension::getIconRenderable method.
   */
  public function testGetIconRenderable(): void {
    $settings = ['foo' => 'bar'];
    $iconMock = $this->createMock(IconDefinitionInterface::class);
    $iconMock->method('getRenderable')
      ->with($settings)
      ->willReturn(['rendered_icon'] + $settings);

    $this->pluginManagerIconPack
      ->method('getIcon')
      ->willReturn($iconMock);

    $result = $this->iconsTwigExtension->getIconRenderable('pack_id', 'icon_id', $settings);
    $this->assertEquals(['rendered_icon'] + $settings, $result);
  }

}
