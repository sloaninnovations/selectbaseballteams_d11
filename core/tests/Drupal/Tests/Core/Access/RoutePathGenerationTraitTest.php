<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\CsrfAccessCheck;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Access\RouteProcessorCsrf;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\InputBag;
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
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected CsrfTokenGenerator $csrfToken;

  /**
   * The route processor.
   *
   * @var \Drupal\Core\Access\RouteProcessorCsrf
   */
  protected RouteProcessorCsrf $processor;

  /**
   * The CSRF access checker.
   *
   * @var \Drupal\Core\Access\CsrfAccessCheck
   */
  protected CsrfAccessCheck $accessCheck;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->csrfToken = $this->getMockBuilder(CsrfTokenGenerator::class)
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
      ->method('hasRequirement')
      ->with('_csrf_token')
      ->willReturn(TRUE);

    // Process the route so the "token" param is generated.
    $routeParams = $params;
    $this->processor->processOutbound('test.example', $route, $routeParams);

    $requestParams = $params + ['token' => $routeParams['token']];

    // Mock Parameter bag.
    $parameterBag = $this->createMock(ParameterBagInterface::class);
    $parameterBag->method('get')->willReturnCallback(function ($key, $default = NULL) use ($requestParams) {
      return $requestParams[$key] ?? $default;
    });
    $parameterBag->method('all')->willReturn($requestParams);

    // InputBag (we must use a real InputBag because it is a final class and cannot be mocked).
    $inputBag = new InputBag($requestParams);

    // Mock Request.
    $request = $this->createMock(Request::class);
    $request->query = $inputBag;

    // Mock RouteMatch.
    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $routeMatch->method('getRawParameters')->willReturn($parameterBag);

    // Check for allowed access.
    $this->assertInstanceOf(AccessResultAllowed::class, $this->accessCheck->access($route, $request, $routeMatch));
  }

  /**
   * Data provider for testCsrfTokenCompleteLifeCycle().
   *
   * @returns array
   */
  public static function providerTestCsrfTokenCompleteLifeCycle(): array {
    return [
      [['param' => 'value']],
      [['param' => '']],
      [['param' => NULL]],
      [[]],
    ];
  }

}
