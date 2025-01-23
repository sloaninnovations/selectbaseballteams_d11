<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\EventSubscriber\FinishResponseSubscriber;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\FinishResponseSubscriber
 * @group EventSubscriber
 */
class FinishResponseSubscriberTest extends UnitTestCase {

  /**
   * The mock Kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $kernel;

  /**
   * The mock language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * The mock request policy.
   *
   * @var \Drupal\Core\PageCache\RequestPolicyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $requestPolicy;

  /**
   * The mock response policy.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $responsePolicy;

  /**
   * The mock cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheContextsManager;

  /**
   * The mock time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $time;

  protected function setUp(): void {
    parent::setUp();

    $this->kernel = $this->createMock(HttpKernelInterface::class);
    $this->languageManager = $this->createMock(LanguageManagerInterface::class);
    $this->requestPolicy = $this->createMock(RequestPolicyInterface::class);
    $this->responsePolicy = $this->createMock(ResponsePolicyInterface::class);
    $this->cacheContextsManager = $this->createMock(CacheContextsManager::class);
    $this->time = $this->createMock(TimeInterface::class);
  }

  /**
   * Finish subscriber should set some default header values.
   *
   * @covers ::onRespond
   */
  public function testDefaultHeaders(): void {
    $finishSubscriber = new FinishResponseSubscriber(
      $this->languageManager,
      $this->getConfigFactoryStub(),
      $this->requestPolicy,
      $this->responsePolicy,
      $this->cacheContextsManager,
      $this->time,
      FALSE
    );

    $this->languageManager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $request = $this->createMock(Request::class);
    $response = $this->createMock(Response::class);
    $response->headers = new ResponseHeaderBag();
    $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

    $finishSubscriber->onRespond($event);

    $this->assertEquals(['en'], $response->headers->all('Content-language'));
    $this->assertEquals(['nosniff'], $response->headers->all('X-Content-Type-Options'));
  }

  /**
   * Finish subscriber should not overwrite existing header values.
   *
   * @covers ::onRespond
   */
  public function testExistingHeaders(): void {
    $finishSubscriber = new FinishResponseSubscriber(
      $this->languageManager,
      $this->getConfigFactoryStub(),
      $this->requestPolicy,
      $this->responsePolicy,
      $this->cacheContextsManager,
      $this->time,
      FALSE
    );

    $this->languageManager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $request = $this->createMock(Request::class);
    $response = $this->createMock(Response::class);
    $response->headers = new ResponseHeaderBag();
    $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

    $response->headers->set('X-Content-Type-Options', 'foo');
    $response->headers->set('X-Frame-Options', 'DENY');

    $finishSubscriber->onRespond($event);

    $this->assertEquals(['en'], $response->headers->all('Content-language'));
    // 'X-Content-Type-Options' will be unconditionally set by core.
    $this->assertEquals(['nosniff'], $response->headers->all('X-Content-Type-Options'));
    $this->assertEquals(['DENY'], $response->headers->all('X-Frame-Options'));
  }

  /**
   * CSP headers should be set from services parameter values.
   *
   * @covers ::onRespond
   */
  public function testConfiguredCspHeaders(): void {
    $finishSubscriber = new FinishResponseSubscriber(
      $this->languageManager,
      $this->getConfigFactoryStub(),
      $this->requestPolicy,
      $this->responsePolicy,
      $this->cacheContextsManager,
      $this->time,
      FALSE,
      [
        'report_only' => "default-src 'self'",
        'enforced' => "script-src * 'unsafe-inline'",
      ]
    );

    $this->languageManager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $request = $this->createMock(Request::class);
    $response = $this->createMock(Response::class);
    $response->headers = new ResponseHeaderBag();
    $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

    $finishSubscriber->onRespond($event);

    $this->assertEquals(["default-src 'self'"], $response->headers->all('Content-Security-Policy-Report-Only'));
    $this->assertEquals(["script-src * 'unsafe-inline'"], $response->headers->all('Content-Security-Policy'));
  }

  /**
   * Data Provider for testing conversion of X-Frame-Options to CSP header.
   *
   * @todo Remove in Drupal 12.0.0. See https://www.drupal.org/project/drupal/issues/3472502
   *
   * @return array
   */
  public static function xFrameOptionsProvider(): array {
    return [
      'not set' => [
        '',
        "script-src * 'unsafe-inline'; object-src 'none'",
      ],
      'sameorigin' => [
        'SAMEORIGIN',
        "script-src * 'unsafe-inline'; object-src 'none'; frame-ancestors 'self'",
      ],
      'deny' => [
        'DENY',
        "script-src * 'unsafe-inline'; object-src 'none'; frame-ancestors 'none'",
      ],
      'domain' => [
        'ALLOW-FROM https://example.com',
        "script-src * 'unsafe-inline'; object-src 'none'; frame-ancestors https://example.com",
      ],
    ];
  }

  /**
   * X-Frame-Options should be converted to a CSP header.
   *
   * @todo Remove in Drupal 12.0.0. See https://www.drupal.org/project/drupal/issues/3472502
   *
   * @dataProvider xFrameOptionsProvider
   *
   * @covers ::onRespondSetCspPolicy
   *
   * @group legacy
   */
  public function testConvertingXFrameOptionsHeader(string $xFrameOptionsValue, string $expected): void {
    $finishSubscriber = new FinishResponseSubscriber(
      $this->languageManager,
      $this->getConfigFactoryStub(),
      $this->requestPolicy,
      $this->responsePolicy,
      $this->cacheContextsManager,
      $this->time,
      FALSE,
      [
        'report_only' => '',
        'enforced' => '',
      ]
    );

    $this->languageManager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $request = $this->createMock(Request::class);
    $response = $this->createMock(Response::class);
    $response->headers = new ResponseHeaderBag();
    $response->headers->set('X-Frame-Options', $xFrameOptionsValue);
    $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

    $finishSubscriber->onRespondSetCspPolicy($event);

    $this->assertEquals([$expected], $response->headers->all('Content-Security-Policy'));
  }

  /**
   * Existing CSP header should not be altered by X-Frame-Options.
   *
   * @todo Remove in Drupal 12.0.0. See https://www.drupal.org/project/drupal/issues/3472502
   *
   * @covers ::onRespond
   * @covers ::onRespondSetCspPolicy
   *
   * @group legacy
   */
  public function testConvertingXFrameOptionsHeaderWithCsp(): void {
    $finishSubscriber = new FinishResponseSubscriber(
      $this->languageManager,
      $this->getConfigFactoryStub(),
      $this->requestPolicy,
      $this->responsePolicy,
      $this->cacheContextsManager,
      $this->time,
      FALSE,
      [
        'report_only' => '',
        'enforced' => "default-src 'self'",
      ]
    );

    $this->languageManager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $request = $this->createMock(Request::class);
    $response = $this->createMock(Response::class);
    $response->headers = new ResponseHeaderBag();
    $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
    $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

    $finishSubscriber->onRespond($event);
    $finishSubscriber->onRespondSetCspPolicy($event);

    $this->assertEquals(["default-src 'self'"], $response->headers->all('Content-Security-Policy'));
  }

}
