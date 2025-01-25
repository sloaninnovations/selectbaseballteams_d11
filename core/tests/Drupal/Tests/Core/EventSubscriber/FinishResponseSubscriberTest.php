<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
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

  /**
   * {@inheritdoc}
   */
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
    $this->assertEquals(['SAMEORIGIN'], $response->headers->all('X-Frame-Options'));
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
   * Finish subscriber outputs tags, context, max-age if debug is on.
   *
   * @covers ::onRespond
   */
  public function testDebugHeaders(): void {
    $finishSubscriber = new FinishResponseSubscriber(
      $this->languageManager,
      $this->getConfigFactoryStub(),
      $this->requestPolicy,
      $this->responsePolicy,
      $this->cacheContextsManager,
      $this->time,
      TRUE
    );

    $this->languageManager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $this->cacheContextsManager->method('optimizeTokens')
      ->willReturn(['context1', 'context2']);

    $request = $this->createMock(Request::class);
    $response = $this->createMock(CacheableResponse::class);
    $response->headers = new ResponseHeaderBag();

    // Set cache tags, context, max-age.
    $cacheData = (new CacheableMetadata())
      ->setCacheTags(['tag1', 'tag2'])
      ->setCacheContexts(['context1', 'context2'])
      ->setCacheMaxAge(123);
    $response->expects($this->any())
      ->method('getCacheableMetadata')
      ->willReturn($cacheData);

    $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
    $finishSubscriber->onRespond($event);

    // Check that X-Drupal-Cache-Tags is in the response header.
    $this->assertEquals(['tag1 tag2'], $response->headers->all('X-Drupal-Cache-Tags'));
    $this->assertEquals(['context1 context2'], $response->headers->all('X-Drupal-Cache-Contexts'));
    $this->assertEquals([123], $response->headers->all('X-Drupal-Cache-Max-Age'));
  }

  /**
   * Tests that the subscriber splits long tags and context into multiple lines.
   *
   * @covers ::onRespond
   */
  public function testLargeTagsAndContexts(): void {
    $finishSubscriber = new FinishResponseSubscriber(
      $this->languageManager,
      $this->getConfigFactoryStub(),
      $this->requestPolicy,
      $this->responsePolicy,
      $this->cacheContextsManager,
      $this->time,
      TRUE
    );

    $this->languageManager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $request = $this->createMock(Request::class);
    $response = $this->createMock(CacheableResponse::class);
    $response->headers = new ResponseHeaderBag();

    // Create a large tag over 8k.
    for ($i = 0; $i < 430; $i++) {
      $tags[] = 'large-tag-name-' . $i;
    }

    // For context, we'll make it over 16k.
    for ($i = 0; $i < 860; $i++) {
      $contexts[] = 'large-context-name-' . $i;
    }
    $this->cacheContextsManager->method('optimizeTokens')
      ->willReturn($contexts);

    $cacheData = (new CacheableMetadata())
      ->setCacheTags($tags);

    $response->expects($this->any())
      ->method('getCacheableMetadata')
      ->willReturn($cacheData);

    $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
    $finishSubscriber->onRespond($event);

    // Check that X-Drupal-Cache-Tags has been split into two.
    $this->assertNotEmpty($response->headers->all('X-Drupal-Cache-Tags-1'));
    // Check that X-Drupal-Cache-Contexts has been split into three.
    $this->assertNotEmpty($response->headers->all('X-Drupal-Cache-Contexts-2'));
  }

}
