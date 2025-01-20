<?php

namespace Drupal\Core\Cache\EventSubscriber;

use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides a path subscriber that converts path aliases.
 */
class CacheTagPreloadSubscriber implements EventSubscriberInterface {

  public function __construct(protected CacheTagsChecksumInterface $cacheTagsChecksum) {
  }

  /**
   * Preloads common cache tags.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   */
  public function onRequest(RequestEvent $event): void {
    if ($event->isMainRequest()) {
      $default_preload_cache_tags = array_merge([
        'route_match',
        'access_policies',
        'routes',
        'router',
        'entity_types',
        'entity_field_info',
        'entity_bundles',
        'local_task',
        'library_info',
      ], Settings::get('cache_preload_tags', []));
      $this->cacheTagsChecksum->getCurrentChecksum($default_preload_cache_tags);
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['onRequest', 500];
    return $events;
  }

}
