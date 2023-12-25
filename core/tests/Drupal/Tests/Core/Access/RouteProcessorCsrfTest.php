<?php

namespace Drupal\Tests\Core\Access;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Access\RouteProcessorCsrf;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Access\RouteProcessorCsrf
 * @group Access
 */
class RouteProcessorCsrfTest extends UnitTestCase {

  /**
   * The mock CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $csrfToken;

  /**
   * The route processor.
   *
   * @var \Drupal\Core\Access\RouteProcessorCsrf
   */
  protected $processor;

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $sessionConfiguration;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->csrfToken = $this->getMockBuilder(CsrfTokenGenerator::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->sessionConfiguration = $this->createMock(SessionConfigurationInterface::class);
    $request_stack = $this->createMock(RequestStack::class);
    // The number this is called differs between tests and is completely
    // irrelevant, the sessionConfiguration mock object will have the exact
    // number of calls.
    $request_stack->expects($this->atMost(1))
      ->method('getCurrentRequest')
      ->willReturn($this->createMock(Request::class));

    $this->processor = new RouteProcessorCsrf($this->csrfToken, $this->sessionConfiguration, $request_stack);
  }

  /**
   * Tests the processOutbound() method with no _csrf_token route requirement.
   */
  public function testProcessOutboundNoRequirement() {
    $this->sessionConfiguration->expects($this->never())
      ->method('hasSession');
    $this->csrfToken->expects($this->never())
      ->method('get');

    $route = new Route('/test-path');
    $parameters = [];

    $bubbleable_metadata = new BubbleableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $bubbleable_metadata);
    // No parameters should be added to the parameters array.
    $this->assertEmpty($parameters);
    // Cacheability of routes without a _csrf_token route requirement is
    // unaffected.
    $this->assertEquals((new BubbleableMetadata()), $bubbleable_metadata);
  }

  /**
   * Tests the processOutbound() method with a _csrf_token route requirement.
   */
  public function testProcessOutbound() {
    $this->sessionConfiguration->expects($this->once())
      ->method('hasSession')
      ->willReturn(TRUE);
    $route = new Route('/test-path', [], ['_csrf_token' => 'TRUE']);
    $parameters = [];

    $bubbleable_metadata = new BubbleableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $bubbleable_metadata);
    // 'token' should be added to the parameters array.
    $this->assertArrayHasKey('token', $parameters);
    // Bubbleable metadata of routes with a _csrf_token route requirement is a
    // placeholder.
    $path = 'test-path';
    $placeholder = Crypt::hashBase64($path);
    $placeholder_render_array = [
      '#lazy_builder' => [
        'route_processor_csrf:renderPlaceholderCsrfToken',
        (array) $path,
      ],
    ];
    $this->assertSame($parameters['token'], $placeholder);
    $this->assertEquals((new BubbleableMetadata())->setAttachments(['placeholders' => [$placeholder => $placeholder_render_array]]), $bubbleable_metadata);
  }

  /**
   * Tests the processOutbound() method for anonymous users.
   */
  public function testProcessOutboundForAnonymous() {
    $this->sessionConfiguration->expects($this->once())
      ->method('hasSession')
      ->willReturn(FALSE);
    $this->csrfToken->expects($this->never())
      ->method('get');
    $route = new Route('/test-path', [], ['_csrf_token' => 'TRUE']);
    $parameters = [];

    $bubbleable_metadata = new BubbleableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $bubbleable_metadata);
    $this->assertEmpty($parameters);
    $this->assertEquals((new BubbleableMetadata()), $bubbleable_metadata);
  }

  /**
   * Tests the processOutbound() method with a dynamic path and one replacement.
   */
  public function testProcessOutboundDynamicOne() {
    $this->sessionConfiguration->expects($this->once())
      ->method('hasSession')
      ->willReturn(TRUE);
    $route = new Route('/test-path/{slug}', [], ['_csrf_token' => 'TRUE']);
    $parameters = ['slug' => 100];

    $bubbleable_metadata = new BubbleableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $bubbleable_metadata);
    // Bubbleable metadata of routes with a _csrf_token route requirement is a
    // placeholder.
    $path = 'test-path/100';
    $placeholder = Crypt::hashBase64($path);
    $placeholder_render_array = [
      '#lazy_builder' => [
        'route_processor_csrf:renderPlaceholderCsrfToken',
        (array) $path,
      ],
    ];
    $this->assertEquals((new BubbleableMetadata())->setAttachments(['placeholders' => [$placeholder => $placeholder_render_array]]), $bubbleable_metadata);
  }

  /**
   * Tests the processOutbound() method with two parameter replacements.
   */
  public function testProcessOutboundDynamicTwo() {
    $this->sessionConfiguration->expects($this->once())
      ->method('hasSession')
      ->willReturn(TRUE);
    $route = new Route('{slug_1}/test-path/{slug_2}', [], ['_csrf_token' => 'TRUE']);
    $parameters = ['slug_1' => 100, 'slug_2' => 'test'];

    $bubbleable_metadata = new BubbleableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $bubbleable_metadata);
    // Bubbleable metadata of routes with a _csrf_token route requirement is a
    // placeholder.
    $path = '100/test-path/test';
    $placeholder = Crypt::hashBase64($path);
    $placeholder_render_array = [
      '#lazy_builder' => [
        'route_processor_csrf:renderPlaceholderCsrfToken',
        (array) $path,
      ],
    ];
    $this->assertEquals((new BubbleableMetadata())->setAttachments(['placeholders' => [$placeholder => $placeholder_render_array]]), $bubbleable_metadata);
  }

}
