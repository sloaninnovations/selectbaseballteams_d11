<?php

declare(strict_types=1);

namespace Drupal\profile_install_requirements\Install;

use Drupal\Core\Extension\InstallRequirementsInterface;

class ProfileInstallRequirementsRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public function getRequirements(): array {
    $requirements['testing_requirements'] = [
      'title' => t('Testing requirements'),
      'severity' => REQUIREMENT_ERROR,
      'description' => t('Testing requirements failed requirements.'),
    ];

    return $requirements;
  }

}
