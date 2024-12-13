<?php

namespace Drupal\Core\File\MimeType;

use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\Event\MimeTypeMapLoadedEvent;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Makes possible to guess the MIME type of a file using its extension.
 */
class ExtensionMimeTypeGuesser implements MimeTypeGuesserInterface {

  use DeprecatedServicePropertyTrait;

  /**
   * The service properties that should raise a deprecation error.
   */
  protected array $deprecatedProperties = [
    'moduleHandler' => 'module_handler',
  ];

  /**
   * Default MIME extension mapping.
   *
   * @var array
   *   Array of mimetypes correlated to the extensions that relate to them.
   */
  protected $defaultMapping = [];

  /**
   * The MIME type map.
   */
  protected MimeTypeMapInterface $map;

  /**
   * Constructs a new ExtensionMimeTypeGuesser.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface|\Drupal\Core\File\MimeType\MimeTypeMapInterface $map
   *   The MIME type map.
   * @param \Drupal\Core\File\FileSystemInterface|null $fileSystem
   *   The file system.
   */
  public function __construct(
    ModuleHandlerInterface | MimeTypeMapInterface $map,
    protected ?FileSystemInterface $fileSystem = NULL,
  ) {
    if (!$map instanceof MimeTypeMapInterface) {
      @trigger_error(
        'Calling ' . __METHOD__ . '() with the $map argument as an instance of \Drupal\Core\Extension\ModuleHandlerInterface is deprecated in drupal:11.2.0 and an instance of \Drupal\Core\File\MimeType\MimeTypeMapInterface is required in drupal:12.0.0. See https://www.drupal.org/node/3494040',
        E_USER_DEPRECATED
      );
      $map = \Drupal::service(MimeTypeMapInterface::class);
    }
    $this->map = $map;
    if (!$this->fileSystem) {
      @trigger_error(
        'Calling ' . __METHOD__ . '() without the $fileSystem argument is deprecated in drupal:11.2.0 and is required in drupal:12.0.0. See https://www.drupal.org/node/3494040',
        E_USER_DEPRECATED
      );
      $this->fileSystem = \Drupal::service('file_system');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function guessMimeType($path): ?string {
    $extension = '';
    $file_parts = explode('.', $this->fileSystem->basename($path));

    // Remove the first part: a full filename should not match an extension,
    // then iterate over the file parts, trying to find a match.
    // For 'my.awesome.image.jpeg', we try: 'awesome.image.jpeg', then
    // 'image.jpeg', then 'jpeg'.
    // We explicitly check for NULL because that indicates that the array is
    // empty.
    while (array_shift($file_parts) !== NULL) {
      $extension = strtolower(implode('.', $file_parts));
      if ($mimeType = $this->map->getMimeTypeForExtension($extension)) {
        return $mimeType;
      }
    }

    return NULL;
  }

  /**
   * Sets the mimetypes/extension mapping to use when guessing mimetype.
   *
   * @param array|null $mapping
   *   Passing a NULL mapping will cause guess() to use self::$defaultMapping.
   *
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
   *   \Drupal\Core\File\MimeType\MimeTypeMapInterface::addMapping() instead.
   *
   * @see https://www.drupal.org/node/3494040
   */
  public function setMapping(?array $mapping = NULL): void {
    @trigger_error(
      __METHOD__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use \Drupal\Core\File\MimeType\MimeTypeMapInterface::addMapping() instead or define your own MimeTypeMapInterface implementation. See https://www.drupal.org/node/3494040',
      E_USER_DEPRECATED
    );
    if (!$this->map instanceof DefaultMimeTypeMap) {
      return;
    }
    // Convert the mapping to be keyed by type.
    $typeMapping = [];
    foreach ($mapping['mimetypes'] as $index => $mimetype) {
      $typeMapping[$mimetype] = array_keys($mapping['extensions'], $index);
    }

    $this->map = new DefaultMimeTypeMap();
    foreach ($typeMapping as $type => $extensions) {
      foreach ($extensions as $extension) {
        $this->map->addMapping($type, $extension);
      }
    }
    \Drupal::service('event_dispatcher')->dispatch(
      new MimeTypeMapLoadedEvent($this->map)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isGuesserSupported(): bool {
    return TRUE;
  }

}
