<?php

declare(strict_types=1);

namespace Drupal\file_test\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Dummy read-only remote stream wrapper (dummy-remote-readonly://).
 */
class DummyRemoteReadOnlyStreamWrapper extends DummyRemoteStreamWrapper {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::READ_VISIBLE;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Dummy remote read-only files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Dummy remote read-only stream wrapper for testing.');
  }

}
