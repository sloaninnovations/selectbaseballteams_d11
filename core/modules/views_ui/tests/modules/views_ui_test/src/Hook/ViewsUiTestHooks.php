<?php

declare(strict_types=1);

namespace Drupal\views_ui_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for views_ui_test.
 */
class ViewsUiTestHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_preview_info_alter().
   *
   * Add a row count row to the live preview area.
   */
  #[Hook('views_preview_info_alter')]
  public function viewsPreviewInfoAlter(&$rows, $view): void {
    $data = ['#markup' => $this->t('Test row count')];
    $data['#attached']['library'][] = 'views_ui_test/views_ui_test.test';
    $rows['query'][] = [['data' => $data], count($view->result)];
  }

}
