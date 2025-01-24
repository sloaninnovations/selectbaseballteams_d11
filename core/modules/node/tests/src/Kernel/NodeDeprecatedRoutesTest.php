<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\TestTools\Extension\DeprecationBridge\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests deprecated Node routes.
 *
 * @group node
 * @group legacy
 */
class NodeDeprecatedRoutesTest extends KernelTestBase {

  use ExpectDeprecationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'system',
  ];

  /**
   * Test URL generation using 'node.add_page' generates deprecated warning.
   */
  public function testNodeAddPageDeprecatedRoute(): void {
    $this->expectDeprecation('The "node.add_page" route is deprecated in drupal:11.2.0 and will be removed in drupal:12.0.0. Use the "entity.node.add_page" route instead. See https://www.drupal.org/node/2940083');
    $url_for_current_route = Url::fromRoute('entity.node.add_page')->toString(TRUE)->getGeneratedUrl();
    $url_for_bc_route = Url::fromRoute('node.add_page')->toString(TRUE)->getGeneratedUrl();
    $this->assertSame($url_for_current_route, $url_for_bc_route);
  }

  /**
   * Test URL generation using 'node.add' generates deprecated warning.
   */
  public function testNodeAddDeprecatedRoute(): void {
    $this->expectDeprecation('The "node.add" route is deprecated in drupal:11.2.0 and will be removed in drupal:12.0.0. Use the "entity.node.add_form" route instead. See https://www.drupal.org/node/2940083');
    $url_for_current_route = Url::fromRoute('entity.node.add_form', ['node_type' => 'page'])->toString(TRUE)->getGeneratedUrl();
    $url_for_bc_route = Url::fromRoute('node.add', ['node_type' => 'page'])->toString(TRUE)->getGeneratedUrl();
    $this->assertSame($url_for_current_route, $url_for_bc_route);
  }

  /**
   * Test route matcher generates deprecated warning for 'node.add_page' route.
   */
  public function testNodeAddPageDeprecatedRouteMatcher(): void {
    $this->expectDeprecation('The "node.add_page" route is deprecated in drupal:11.2.0 and will be removed in drupal:12.0.0. Use the "entity.node.add_page" route instead. See https://www.drupal.org/node/2940083');
    $url_for_current_route = Url::fromRoute('entity.node.add_page')->toString(TRUE)->getGeneratedUrl();
    $sub_request = new Request();
    $sub_request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'node.add_page');
    $sub_request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $this->container->get('router.route_provider')->getRouteByName('node.add_page'));
    \Drupal::requestStack()->push($sub_request);
    $url_for_bc_route = Url::fromRouteMatch(\Drupal::routeMatch())->toString(TRUE)->getGeneratedUrl();
    \Drupal::requestStack()->pop();
    $this->assertSame($url_for_current_route, $url_for_bc_route);
  }

  /**
   * Test route matcher generates deprecated warning for 'node.add' route.
   */
  public function testNodeAddDeprecatedRouteMatcher(): void {
    $this->expectDeprecation('The "node.add" route is deprecated in drupal:11.2.0 and will be removed in drupal:12.0.0. Use the "entity.node.add_form" route instead. See https://www.drupal.org/node/2940083');
    $url_for_current_route = Url::fromRoute('entity.node.add_form', ['node_type' => 'page'])->toString(TRUE)->getGeneratedUrl();
    $sub_request = new Request();
    $sub_request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'node.add');
    $sub_request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $this->container->get('router.route_provider')->getRouteByName('node.add'));
    $sub_request->attributes->set('_raw_variables', new ParameterBag(['node_type' => 'page']));
    \Drupal::requestStack()->push($sub_request);
    $url_for_bc_route = Url::fromRouteMatch(\Drupal::routeMatch())->toString(TRUE)->getGeneratedUrl();
    \Drupal::requestStack()->pop();
    $this->assertSame($url_for_current_route, $url_for_bc_route);
  }

}
