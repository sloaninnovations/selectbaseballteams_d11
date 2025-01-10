<?php

declare(strict_types=1);

namespace Drupal\module_update_requirements\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for module_update_requirements.
 */
class ModuleUpdateRequirementsHooks {

  /**
   * Implements hook_update_requirements().
   */
  #[Hook('update_requirements')]
  public function updateRequirements(): array {
    return [
      'test.update.error' => [
        'title' => t('UpdateError'),
        'value' => t('None'),
        'description' => t('Update Error.'),
        'severity' => REQUIREMENT_ERROR,
      ],
      'test.update.error.alter' => [
        'title' => t('UpdateError'),
        'value' => t('None'),
        'description' => t('Update Error.'),
        'severity' => REQUIREMENT_ERROR,
      ],
    ];
  }

  /**
   * Implements hook_update_requirements_alter().
   */
  #[Hook('update_requirements_alter')]
  public function updateRequirementsAlter(array &$requirements): void {
    $requirements['test.update.error.alter'] = [
      'title' => t('UpdateWarning'),
      'value' => t('None'),
      'description' => t('Update Warning.'),
      'severity' => REQUIREMENT_WARNING,
    ];
  }

}
