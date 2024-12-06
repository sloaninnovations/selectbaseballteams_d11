<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_link_content\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;

/**
 * Tests menu cache invalidation.
 *
 * @group menu_link_content
 */
class MenuCacheTagInvalidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
    'menu_link_content',
    'menu_link_content_invalidation_tracker',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('menu_link_content');

    Menu::create([
      'id' => 'menu-test',
      'label' => 'Test menu',
      'description' => 'Description text',
    ])->save();
  }

  /**
   * When saving a menu link with no changes, cache shouldn't be invalidated.
   */
  public function testMenuLinkInvalidation(): void {

    $cacheInvalidationLogger = $this->container->get('menu_link_content_invalidation_tracker.cache_invalidator_logger');

    $link = [
      'title' => 'Menu link test',
      'provider' => 'module_test',
      'menu_name' => 'menu-test',
      'link' => ['uri' => 'internal:/foobar'],
    ];
    $link = MenuLinkContent::create($link);
    $link->save();

    $tags = $cacheInvalidationLogger->getInvalidatedTags();
    $this->assertContains('config:system.menu.menu-test', array_unique($tags));

    $cacheInvalidationLogger->resetInvalidatedTags();
    $link->save();
    $tags = $cacheInvalidationLogger->getInvalidatedTags();
    $this->assertNotContains('config:system.menu.menu-test', array_unique($tags));

  }

}
