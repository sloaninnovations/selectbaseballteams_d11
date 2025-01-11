<?php

declare(strict_types=1);

namespace Drupal\module_install_unmet_requirements\Install;

use Drupal\Core\Extension\InstallRequirementsInterface;

class ModuleInstallUnmetRequirementsRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    $requirements['testing_requirements'] = [
      'title' => t('Testing requirements'),
      'severity' => REQUIREMENT_ERROR,
      'description' => t('Testing requirements failed requirements.'),
    ];

    return $requirements;
  }

}
