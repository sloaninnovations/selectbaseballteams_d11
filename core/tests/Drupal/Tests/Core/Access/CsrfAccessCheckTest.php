<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\Access\CsrfAccessCheck;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Access\CsrfAccessCheck
 * @group Access
 */
class CsrfAccessCheckTest extends UnitTestCase {

  /**
   * The mock CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $csrfToken;

  /**
   * The access checker.
   *
   * @var \Drupal\Core\Access\CsrfAccessCheck
   */
  protected $accessCheck;

  /**
   * The mock route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeMatch;

  /**
   * The mock parameter bag.
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag
   */
  protected $parameterBag;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->csrfToken = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();

    $this->parameterBag = $this->createMock(ParameterBagInterface::class);

    $this->routeMatch = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');

    $this->accessCheck = new CsrfAccessCheck($this->csrfToken);
  }

  /**
   * Tests the access() method with a valid token.
   */
  public function testAccessTokenPass(): void {
    $this->csrfToken->expects($this->once())
      ->method('validate')
      ->with('test_query', 'test-path/42')
      ->willReturn(TRUE);

    $this->parameterBag
      ->method('all')
      ->willReturn(['node' => 42]);

    $this->routeMatch->expects($this->once())
      ->method('getRawParameters')
      ->willReturn($this->parameterBag);

    $route = new Route('/test-path/{node}', [], ['_csrf_token' => 'TRUE']);
    $request = Request::create('/test-path/42?token=test_query');

    $this->assertEquals(AccessResult::allowed()->setCacheMaxAge(0), $this->accessCheck->access($route, $request, $this->routeMatch));
  }

  /**
   * @covers ::access
   */
  public function testCsrfTokenInvalid(): void {
    $this->csrfToken
      ->method('validate')
      ->with('test_query', 'test-path')
      ->willReturn(FALSE);

    $this->parameterBag
      ->method('all')
      ->willReturn([]);

    $this->routeMatch
      ->method('getRawParameters')
      ->willReturn($this->parameterBag);

    $route = new Route('/test-path', [], ['_csrf_token' => 'TRUE']);
    $request = Request::create('/test-path?token=test_query');

    $this->assertEquals(AccessResult::forbidden("'csrf_token' URL query argument is invalid.")->setCacheMaxAge(0), $this->accessCheck->access($route, $request, $this->routeMatch));
  }

  /**
   * @covers ::access
   */
  public function testCsrfTokenMissing(): void {
    $this->csrfToken
      ->method('validate')
      ->with('', 'test-path')
      ->willReturn(FALSE);

    $this->parameterBag
      ->method('all')
      ->willReturn([]);

    $this->routeMatch
      ->method('getRawParameters')
      ->willReturn($this->parameterBag);

    $route = new Route('/test-path', [], ['_csrf_token' => 'TRUE']);
    $request = Request::create('/test-path');
    $this->assertEquals(AccessResult::forbidden("'csrf_token' URL query argument is missing.")->setCacheMaxAge(0), $this->accessCheck->access($route, $request, $this->routeMatch));
  }

  /**
   * Tests the access() method with a previously valid token.
   *
   * After a change in https://www.drupal.org/project/drupal/issues/2031149 CSRF tokens
   * are now validated against a slightly different processed route path. We must ensure
   * that previously valid CSRF tokens are still valid.
   *
   * @covers ::access
   * @see https://www.drupal.org/project/drupal/issues/2031149
   */
  public function testLegacyAccessTokenPass(): void {

    $this->parameterBag
      ->method('all')
      ->willReturn(['node' => 42]);

    $this->routeMatch->expects($this->exactly(2))
      ->method('getRawParameters')
      ->willReturn($this->parameterBag);

    $token = 'test_token';

    $route = new Route('/test-path/{node}/{optional}', ['optional' => ''], ['_csrf_token' => 'TRUE']);
    $request = Request::create('/test-path/42?token=' . $token);

    $path = $this->accessCheck->generateRoutePath($route, $this->parameterBag->all());
    $legacyPath = $this->accessCheck->generateLegacyRoutePath($route, $this->parameterBag->all());

    // Mock a CSRF token validation that passes only for the legacy path
    // and check that the validate method is called two times
    // (one with the path and other with the legacy path).
    $this->csrfToken->expects($this->exactly(2))
      ->method('validate')
      ->willReturnMap([
        [$token, $path, FALSE],
        [$token, $legacyPath, TRUE],
      ]);

    $this->assertEquals(AccessResult::allowed()->setCacheMaxAge(0), $this->accessCheck->access($route, $request, $this->routeMatch));
  }

}
