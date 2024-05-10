<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Unit;

use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\navigation\Event\NavigationLinkTreeEvent;
use Drupal\navigation\EventSubscriber\NavigationMenuLinkTreeSubscriber;
use Drupal\Tests\Core\Menu\MenuLinkMock;
use Drupal\Tests\UnitTestCase;

class NavigationMenuLinkTreeSubscriberTest extends UnitTestCase {

  /**
   * The original menu tree build in mockTree().
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeElement[]
   */
  protected $originalTree = [];

  /**
   * Array of menu link instances.
   *
   * @var \Drupal\Core\Menu\MenuLinkInterface[]
   */
  protected $links = [];

  /**
   * The navigation link tree subscriber.
   *
   * @var \Drupal\navigation\EventSubscriber\NavigationMenuLinkTreeSubscriber
   */
  protected $subscriber;

  protected function setUp(): void {
    parent::setUp();

    $this->subscriber = new NavigationMenuLinkTreeSubscriber();
  }

  /**
   * Tests the menu link tree manipulation logic.
   */
  public function testOnLinkTreeManipulate(): void {
    $this->mockTree();
    $event = new NavigationLinkTreeEvent($this->originalTree);
    $this->subscriber->onLinkTreeManipulate($event);

    $this->assertCount(1, $event->getMenuLinkTree());
  }

  /**
   * Creates a mock tree.
   */
  protected function mockTree() {
    $this->links = [
      1 => MenuLinkMock::create(['id' => 'help.main', 'route_name' => 'example1', 'title' => 'foo', 'parent' => '', 'menu_name' => 'admin']),
      2 => MenuLinkMock::create(['id' => 'help.main', 'route_name' => 'example2', 'title' => 'foo', 'parent' => '']),
      3 => MenuLinkMock::create(['id' => 'system.admin_content', 'route_name' => 'example3', 'title' => 'bar', 'parent' => '', 'menu_name' => 'admin']),
      4 => MenuLinkMock::create(['id' => 'system.admin_content_child', 'route_name' => 'example4', 'title' => 'baz', 'parent' => 'system.admin_content', 'menu_name' => 'admin']),
      5 => MenuLinkMock::create(['id' => 'system.admin_content_child_2', 'route_name' => 'example5', 'title' => 'baz', 'parent' => 'system.admin_content', 'menu_name' => 'admin']),
    ];
    $this->originalTree = [];
    $this->originalTree[1] = new MenuLinkTreeElement($this->links[1], FALSE, 1, FALSE, []);
    $this->originalTree[2] = new MenuLinkTreeElement($this->links[2], FALSE, 1, FALSE, []);
    $this->originalTree[3] = new MenuLinkTreeElement($this->links[3], TRUE, 1, FALSE, [
      3 => new MenuLinkTreeElement($this->links[4], FALSE, 2, FALSE, []),
    ]);
    $this->originalTree[5] = new MenuLinkTreeElement($this->links[5], FALSE, 1, FALSE, []);
  }

}
