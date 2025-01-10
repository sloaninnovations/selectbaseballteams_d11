<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Routing\RouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\RequestHandler;
use Drupal\rest\ResourceResponse;
use Drupal\rest\RestResourceConfigInterface;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Test REST RequestHandler controller logic.
 *
 * @group rest
 * @coversDefaultClass \Drupal\rest\RequestHandler
 */
class RequestHandlerTest extends KernelTestBase {

  /**
   * @var \Drupal\rest\RequestHandler
   */
  protected $requestHandler;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['serialization', 'rest'];

  /**
   * The entity storage.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityStorage;
  /**
   * Request body used in the test request.
   *
   * @var array
   */
  protected $requestBody;

  /**
   * Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * REST resource config.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->requestBody = ['this is an array'];

    $serializer = $this->prophesize(SerializerInterface::class);
    $serializer->willImplement(DecoderInterface::class);
    $serializer->decode(Json::encode($this->requestBody), 'json', Argument::type('array'))
      ->willReturn($this->requestBody);

    $this->requestHandler = new RequestHandler($serializer->reveal());
    $this->request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], Json::encode($this->requestBody));

    // Set up the configuration.
    $this->config = $this->prophesize(RestResourceConfigInterface::class);
    $this->config->getCacheContexts()
      ->willReturn([]);
    $this->config->getCacheTags()
      ->willReturn([]);
    $this->config->getCacheMaxAge()
      ->willReturn(12);
  }

  /**
   * @covers ::handle
   */
  public function testHandle(): void {
    $route_match = new RouteMatch('test', (new Route('/rest/test', ['_rest_resource_config' => 'rest_plugin', 'example' => ''], ['_format' => 'json']))->setMethods(['GET']));

    $resource = $this->prophesize(StubRequestHandlerResourcePlugin::class);
    $resource->get('', $this->request)
      ->shouldBeCalled();
    $resource->getPluginDefinition()
      ->willReturn([])
      ->shouldBeCalled();

    $this->config->getResourcePlugin()
      ->willReturn($resource->reveal());

    // Response returns NULL this time because response from plugin is not
    // a ResourceResponse so it is passed through directly.
    $response = $this->requestHandler->handle($route_match, $this->request, $this->config->reveal());
    $this->assertEquals(NULL, $response);

    // Response will return a ResourceResponse this time.
    $response = new ResourceResponse([]);
    $resource->get(NULL, $this->request)
      ->willReturn($response);
    $handler_response = $this->requestHandler->handle($route_match, $this->request, $this->config->reveal());
    $this->assertEquals($response, $handler_response);

    // We will call the patch method this time.
    $route_match = new RouteMatch('test', (new Route('/rest/test', ['_rest_resource_config' => 'rest_plugin', 'example_original' => ''], ['_content_type_format' => 'json']))->setMethods(['PATCH']));
    $this->request->setMethod('PATCH');
    $response = new ResourceResponse([]);
    $resource->patch(['this is an array'], $this->request)
      ->shouldBeCalledTimes(1)
      ->willReturn($response);
    $handler_response = $this->requestHandler->handle($route_match, $this->request, $this->config->reveal());
    $this->assertEquals($response, $handler_response);
  }

  /**
   * Tests that request body is passed to any method supporting request body.
   *
   * @dataProvider providerHttpMethodsWithBody
   */
  public function testHandlePassesRequestBody($httpMethod, $classMethod) {
    $defaults = [
      '_rest_resource_config' => 'restplugin',
      'example_original' => '',
    ];
    $route = new Route('/rest/test', $defaults, ['_content_type_format' => 'json']);
    $route->setMethods([$httpMethod]);
    $route_match = new RouteMatch('test', $route);

    $response = new ResourceResponse([]);
    $resource = $this->prophesize(StubRequestHandlerResourcePlugin::class);
    $resource->__call($classMethod, [$this->requestBody, $this->request])
         ->shouldBeCalledTimes(1)
         ->willReturn($response);
    $resource->getPluginDefinition()
      ->willReturn([])
      ->shouldBeCalled();

    $this->config->getResourcePlugin()
      ->willReturn($resource->reveal());

    $this->request->setMethod($httpMethod);
    $handler_response = $this->requestHandler->handle($route_match, $this->request, $this->config->reveal());
       $this->assertEquals($response, $handler_response);
  }

  /**
   * Returns array of arrays.
   *
   * Each array contains HTTP method name supporting
   * request body and corresponding method name used in REST resource plugins
   * which handles the HTTP request.
   */
  public function providerHttpMethodsWithBody() {
    return [
      ['POST', 'post'],
      ['PATCH', 'patch'],
      ['PUT', 'put'],
      ['DELETE', 'delete'],
    ];
  }
}

/**
 * Stub class where we can prophesize methods.
 */
class StubRequestHandlerResourcePlugin extends ResourceBase {

  public function get($example = NULL, ?Request $request = NULL) {}

  public function post($data, Request $request) {}

  public function patch($data, Request $request) {}

  public function delete($data, Request $request) {}

  public function put($data, Request $request) {}

}
