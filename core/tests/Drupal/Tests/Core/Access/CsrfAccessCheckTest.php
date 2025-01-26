<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Routing\RouteMatchInterface;
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
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected CsrfTokenGenerator $csrfToken;

  /**
   * The access checker.
   *
   * @var \Drupal\Core\Access\CsrfAccessCheck
   */
  protected CsrfAccessCheck $accessCheck;

  /**
   * The mock route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The mock parameter bag.
   *
   * @var \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface
   */
  protected ParameterBagInterface $parameterBag;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->csrfToken = $this->getMockBuilder(CsrfTokenGenerator::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->parameterBag = $this->createMock(ParameterBagInterface::class);

    $this->routeMatch = $this->createMock(RouteMatchInterface::class);

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
   * After a bug fix, CSRF tokens are now validated against a fully processed route path,
   * with all parameters replaced (optional ones as well). To maintain backward compatibility,
   * we need to ensure that previously valid CSRF tokens remain valid.
   *
   * @covers ::access
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
