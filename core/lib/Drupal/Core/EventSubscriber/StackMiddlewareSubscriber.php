<?php

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to pop request from request stack pushed by Drupal kernel.
 *
 * KernelEvents::TERMINATE subscriber, to pop the current Request
 * object from the request stack at the end of the execution cycle.
 *
 * The last line of index.php calls DrupalKernel::terminate(), which
 * calls StackedHttpKernel::terminate(), which loops through any
 * middleware kernels with terminate() methods, ending with
 * HttpKernel::terminate().
 *
 * HttpKernel::terminate() issues KernelEvents::TERMINATE. There are
 * other subscribers to KernelEvents::TERMINATE that require the
 * current Request object, so the pop from the request stack must
 * occur after these have run. StackMiddlewareSubscriber has the
 * lowest priority so that it runs last of all. Only a few object
 * __destruct() methods run after KernelEvents::TERMINATE completes
 * and these should not require the current Request object.
 *
 * @see \Drupal\Core\DrupalKernel::preHandle()
 */
class StackMiddlewareSubscriber implements EventSubscriberInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new StackMiddlewareSubscriber instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * Pops the current Request object from the request stack.
   *
   * This ensures the request stack is populated until the very end of the
   * execution cycle, right before any __destruct() functions are run.
   *
   * @param \Symfony\Component\HttpKernel\Event\TerminateEvent $event
   *   The event to process.
   */
  public function onKernelTerminate(TerminateEvent $event): void {
    $this->requestStack->pop();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // To be called last, this must be the lowest numbered TERMINATE subscriber.
    // The lowest numbered Symfony TERMINATE subscriber is ProfilerListener,
    // with priority -1024.
    return [
      KernelEvents::TERMINATE => ['onKernelTerminate', -4096],
    ];
  }

}
