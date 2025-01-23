<?php

namespace Drupal\migrate\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateMemoryLimitEvent;
use Drupal\migrate\MemoryManagerInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateMessageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener to reclaim memory when limits exceeded.
 */
class MemoryLimitExceeded implements EventSubscriberInterface {

  /**
   * The migrate message class.
   *
   * @var \Drupal\migrate\MigrateMessageInterface
   */
  protected MigrateMessageInterface $message;

  /**
   * Constructs MemoryLimitExceeded object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {
    $this->message = new MigrateMessage();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[MigrateEvents::MEMORY_LIMIT][] = ['reclaim', 0];
    $events[MigrateEvents::MEMORY_LIMIT][] = ['notify', 0];
    return $events;
  }

  /**
   * Tries to reclaim memory.
   *
   * @param \Drupal\migrate\Event\MigrateMemoryLimitEvent $event
   *   The migrate memory limit event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function reclaim(MigrateMemoryLimitEvent $event): void {
    if ($event->getPhase() != MemoryManagerInterface::PRE_RECLAIMED) {
      return;
    }
    // First, try resetting Drupal's static storage - this frequently releases
    // plenty of memory to continue.
    drupal_static_reset();

    // Entity storage can blow up with caches so clear them out.
    foreach ($this->entityTypeManager->getDefinitions() as $id => $definition) {
      $this->entityTypeManager->getStorage($id)->resetCache();
    }

    // @todo Explore resetting the container.
    //   https://www.drupal.org/node/3128793
    // Run garbage collector to further reduce memory.
    gc_collect_cycles();
  }

  /**
   * Displays a memory usage message.
   *
   * @param \Drupal\migrate\Event\MigrateMemoryLimitEvent $event
   *   A migrate memory limit event.
   */
  public function notify(MigrateMemoryLimitEvent $event): void {
    $results = [
      '@pct' => round($event->getUsageRatio() * 100),
      '@usage' => ByteSizeMarkup::create($event->getUsageInBytes()),
      '@limit' => ByteSizeMarkup::create($event->getLimit()),
    ];
    switch ($event->getPhase()) {
      case MemoryManagerInterface::PRE_RECLAIMED:
        $this->message->display(new TranslatableMarkup(
          'Memory usage is @usage (@pct% of limit @limit), reclaiming memory.', $results), 'warning');
        break;

      case MemoryManagerInterface::STILL_EXCEEDED:
        $this->message->display(new TranslatableMarkup(
          'Memory usage is now @usage (@pct% of limit @limit), not enough reclaimed, starting new batch', $results), 'warning');
        break;

      case MemoryManagerInterface::REDUCED_ENOUGH_TO_CONTINUE:
      case '':
        $this->message->display(new TranslatableMarkup(
          'Memory usage is now @usage (@pct% of limit @limit), reclaimed enough, continuing', $results));
        break;
    }
  }

}
