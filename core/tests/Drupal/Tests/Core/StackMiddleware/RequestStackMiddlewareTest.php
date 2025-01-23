<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StackMiddleware;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests the usage of the request stack as part of request processing.
 *
 * @group StackMiddleware
 */
class RequestStackMiddlewareTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['http_kernel_test', 'system'];

  /**
   * Tests that no request left in request stack when response send.
   *
   * The StackedHttpKernel::handle() does a RequestStack::push()
   * and StackedHttpKernel::terminate() does a RequestStack::pop().
   *
   * It compares master, current and parent Request objects
   * before and after StackedHttpKernel::handle(),
   * StackedHttpKernel::terminate(), and some simple
   * push() & pop() calls.
   */
  public function testRequest(): void {
    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = \Drupal::service('request_stack');

    $request_1 = Request::create('/user/login');
    $request_1->setSession(new Session(new MockArraySessionStorage()));
    $request_2 = Request::create('/');
    $request_2->setSession(new Session(new MockArraySessionStorage()));
    $request_3 = Request::create((new Url('http_kernel_test.empty'))->toString());
    $request_3->setSession(new Session(new MockArraySessionStorage()));

    // Push request 1.
    // Stack top = request 1.
    $request_stack->push($request_1);

    $main_1 = $request_stack->getMainRequest();
    $current_1 = $request_stack->getCurrentRequest();

    $this->assertSame($current_1, $request_1);

    // Push request 2.
    // Stack top = request 2.
    $request_stack->push($request_2);

    $main_2 = $request_stack->getMainRequest();
    $current_2 = $request_stack->getCurrentRequest();
    $parent_2 = $request_stack->getParentRequest();

    $this->assertSame($main_1, $main_2);
    $this->assertSame($current_2, $request_2);
    $this->assertSame($parent_2, $request_1);

    // Pop request 2.
    // Stack top = request 1.
    $request_stack->pop();

    $main_3 = $request_stack->getMainRequest();
    $current_3 = $request_stack->getCurrentRequest();

    $this->assertSame($main_1, $main_3);
    $this->assertSame($current_3, $request_1);

    /** @var \Stack\StackedHttpKernel $http_kernel */
    $http_kernel = \Drupal::service('http_kernel');
    // Handle request 3 = push request 3.
    // Stack top = request 3.
    $response = $http_kernel->handle($request_3);

    $main_4 = $request_stack->getMainRequest();
    $current_4 = $request_stack->getCurrentRequest();
    $parent_4 = $request_stack->getParentRequest();

    $this->assertSame($main_1, $main_4);
    $this->assertSame($current_4, $request_3);
    $this->assertSame($parent_4, $request_1);

    // Terminate request 3 = pop request 3.
    // Stack top = request 1.
    $http_kernel->terminate($request_3, $response);

    $main_5 = $request_stack->getMainRequest();
    $current_5 = $request_stack->getCurrentRequest();

    $this->assertSame($main_1, $main_5);
    $this->assertSame($current_5, $request_1);
  }

}
