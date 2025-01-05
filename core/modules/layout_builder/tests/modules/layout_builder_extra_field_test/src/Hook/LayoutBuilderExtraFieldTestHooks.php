<?php

declare(strict_types=1);

namespace Drupal\layout_builder_extra_field_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for layout_builder_extra_field_test.
 */
class LayoutBuilderExtraFieldTestHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo() {
    $extra['node']['bundle_with_section_field']['display']['layout_builder_extra_field_test'] = [
      'label' => $this->t('New Extra Field'),
      'description' => $this->t('New Extra Field description'),
      'weight' => 0,
    ];
    return $extra;
  }

}
