<?php

declare(strict_types=1);

namespace Drupal\comment_empty_title_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for comment_empty_title_test.
 */
class CommentEmptyTitleTestHooks {

  /**
   * Implements hook_preprocess_comment().
   */
  #[Hook('preprocess_comment')]
  function preprocessComment(&$variables): void {
    $variables['title'] = '';
  }

}
