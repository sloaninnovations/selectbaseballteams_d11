<?php

namespace Drupal\Core\StackMiddleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Request stack that controls the lifecycle of requests.
 *
 * This version wraps the RequestStack push() and pop() functions to prevent
 * the caller from pushing / popping to the normal request stack. The other
 * functions of RequestStack work as normal.
 *
 * RequestStackWrapper is injected into Symfony's HttpKernel via its
 * constructor.
 *
 * This is necessary because Drupal needs the request stack to be populated
 * before HttpKernel::handleRaw() calls push() and after
 * HttpKernel::finishRequest() calls pop(). This also prevents both Drupal and
 * Symfony pushing the same request to RequestStack twice and only popping the
 * request once.
 *
 * Drupal pushes to RequestStack in DrupalKernel::preHandle(), called from
 * KernelPreHandle::handle(). Drupal pops from RequestStack in
 * StackMiddleWareSubscriber, the very last subscriber to
 * KernelEvents::TERMINATE, called right before end of the execution cycle.
 *
 * @internal
 */
class RequestStackWrapper extends RequestStack {

  /**
   * The wrapped request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $wrappedRequestStack;

  /**
   * Local request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request[]
   */
  private $localRequests = [];

  /**
   * Constructs a new RequestStackWrapper.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(RequestStack $requestStack) {
    $this->wrappedRequestStack = $requestStack ?: new RequestStack();
  }

  /**
   * {@inheritdoc}
   */
  public function push(Request $request): void {
    $this->localRequests[] = $request;
  }

  /**
   * {@inheritdoc}
   */
  public function pop(): ?Request {
    if (!$this->localRequests) {
      return NULL;
    }

    return array_pop($this->localRequests);
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentRequest(): ?Request {
    return $this->wrappedRequestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getMainRequest(): ?Request {
    return $this->wrappedRequestStack->getMainRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getParentRequest(): ?Request {
    return $this->wrappedRequestStack->getParentRequest();
  }

}
