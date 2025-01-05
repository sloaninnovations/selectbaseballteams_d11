<?php

declare(strict_types=1);

namespace Drupal\file_test\StreamWrapper;

use Drupal\Core\StreamWrapper\LocalReadOnlyStream;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Helper class for testing the stream wrapper registry.
 *
 * Dummy stream wrapper implementation (dummy-readonly://).
 */
class DummyReadOnlyStreamWrapper extends LocalReadOnlyStream {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Dummy files (readonly)');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Dummy wrapper for testing (readonly).');
  }

  public function getDirectoryPath() {
    return \Drupal::getContainer()->getParameter('site.path') . '/files';
  }

  /**
   * Override getInternalUri().
   *
   * Return a dummy path for testing.
   */
  public function getInternalUri() {
    return '/dummy/example.txt';
  }

  /**
   * Override getExternalUrl().
   *
   * Return the HTML URI of a public file.
   */
  public function getExternalUrl() {
    return '/dummy/example.txt';
  }

}
