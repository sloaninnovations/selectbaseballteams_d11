<?php

declare(strict_types=1);

namespace Drupal\module_runtime_requirements\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for module_runtime_requirements.
 */
class ModuleRuntimeRequirementsHooks {

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtimeRequirements(): array {
    return [
      'test.runtime.error' => [
        'title' => t('RuntimeError'),
        'value' => t('None'),
        'description' => t("Runtime Error."),
        'severity' => REQUIREMENT_ERROR,
      ],
    ];
  }

}
