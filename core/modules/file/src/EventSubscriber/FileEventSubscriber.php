<?php

namespace Drupal\file\EventSubscriber;

use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\file\FileSanitizeName;

/**
 * Sanitizes uploaded filenames.
 *
 * @package Drupal\file\EventSubscriber
 */
class FileEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new file event listener.
   *
   * @param \Drupal\file\FileSanitizeName $fileSanitizeName
   *   The sanitize filename service.
   */
  public function __construct(
    protected FileSanitizeName $fileSanitizeName,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      FileUploadSanitizeNameEvent::class => 'sanitizeFilename',
    ];
  }

  /**
   * Sanitizes the filename of a file being uploaded.
   *
   * @param \Drupal\Core\File\Event\FileUploadSanitizeNameEvent $event
   *   File upload sanitize name event.
   *
   * @see file_form_system_file_system_settings_alter()
   */
  public function sanitizeFilename(FileUploadSanitizeNameEvent $event) {
    $filename = $event->getFilename();
    $filename = $this->fileSanitizeName->sanitizeFilename($filename);
    $event->setFilename($filename);
  }

}
