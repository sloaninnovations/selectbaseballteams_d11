<?php

declare(strict_types=1);

namespace Drupal\layout_builder\EventSubscriber;

use Drupal\Component\Utility\NestedArray;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * View subscriber that alters render arrays for forms with layout builder.
 */
final class LayoutFormViewSubscriber implements EventSubscriberInterface {

  /**
   * Alters the controller result render array for forms using layout builder.
   *
   * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
   *   The event to process.
   */
  public function onViewRenderArray(ViewEvent $event): void {
    $result = $event->getControllerResult();
    if (!is_array($result) ||
        !isset($result['#type']) ||
        ($result['#type'] !== 'form') ||
        !isset($result['#layout_builder_element_keys'])) {
      return;
    }

    // If the form render element has a #layout_builder_element_keys property,
    // first set the form element as a child of the root render array. Use the
    // keys to get the layout builder element from the form render array and
    // copy it to a separate child element of the root element to prevent any
    // forms within the layout builder element from being nested.
    $build['form'] = &$result;
    $layout_builder_element = &NestedArray::getValue($result, $result['#layout_builder_element_keys']);
    $build['layout_builder'] = $layout_builder_element;
    // Remove the layout builder element within the form.
    $layout_builder_element = [];
    $event->setControllerResult($build);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // Needs a higher priority than
      // MainContentViewSubscriber::onViewRenderArray(), which is default 0.
      KernelEvents::VIEW => ['onViewRenderArray', 5],
    ];
  }

}
