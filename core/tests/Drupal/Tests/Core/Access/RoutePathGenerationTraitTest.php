<?php

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\CsrfAccessCheck;
use Drupal\Core\Access\RouteProcessorCsrf;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @covers \Drupal\Core\Access\RoutePathGenerationTrait
 * @group Access
 */
class RoutePathGenerationTraitTest extends UnitTestCase {

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
   * The CSRF access checker.
   *
   * @var \Drupal\Core\Access\CsrfAccessCheck
   */
  protected $accessCheck;

  protected function setUp(): void {
    $this->csrfToken = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    // Make CsrfTokenGenerator mock use a simple hash of the value
    // passed as parameter, as it is enough for the sake of our tests.
    $this->csrfToken->method('get')->willReturnCallback(function ($value) {
      return hash('sha256', $value);
    });
    $this->csrfToken->method('validate')->willReturnCallback(function ($token, $value) {
      return $token === hash('sha256', $value);
    });
    $this->processor = new RouteProcessorCsrf($this->csrfToken);
    $this->accessCheck = new CsrfAccessCheck($this->csrfToken);
  }

  /**
   * Tests CSRF token creation/validation consistency between CsrfAccessCheck and RouteProcessorCsrf.
   *
   * Multiple cases are provided for an optional parameter (non-empty, empty, null, undefined).
   *
   * @dataProvider providerTestCsrfTokenCompleteLifeCycle
   */
  public function testCsrfTokenCompleteLifeCycle($params): void {

    // Mock a route.
    $route = $this->createMock(Route::class);
    $route
      ->method('getPath')
      ->willReturn('test/example/{param}');
    $route
      ->expects($this->any())
      ->method('hasRequirement')
      ->with('_csrf_token')
      ->willReturn(TRUE);

    // Process the route so the "token" param is generated.
    $routeParams = $params;
    $this->processor->processOutbound('test.example', $route, $routeParams);

    // Mock a Request and a RouteMatch with the params plus the generated token.
    $requestParams = $params + ['token' => $routeParams['token']];
    $parameterBag = $this->createMock(ParameterBagInterface::class);
    $parameterBag->method('get')->willReturnCallback(function ($key, $default = NULL) use ($requestParams) {
      return $requestParams[$key] ?? $default;
    });
    $parameterBag->method('all')->willReturn($requestParams);
    $request = $this->createMock(Request::class);
    $request->query = $parameterBag;
    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $routeMatch->method('getRawParameters')->willReturn($parameterBag);

    // Check for allowed access.
    $this->assertInstanceOf(AccessResultAllowed::class, $this->accessCheck->access($route, $request, $routeMatch));
  }

  public function providerTestCsrfTokenCompleteLifeCycle() {
    return [
      [['param' => 'value']],
      [['param' => '']],
      [['param' => NULL]],
      [[]],
    ];
  }

}
