<?php

declare(strict_types=1);

namespace Drupal\profile_install_requirements\Install;

use Drupal\Core\Extension\InstallRequirementsInterface;

class Requirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public function getRequirements(): array {
    $GLOBALS['profile_install_requirements'] = 'profile_install_requirements';

    return [];
  }

}
