<?php

declare(strict_types=1);

namespace Drupal\module_install_requirements\Install;

use Drupal\Core\Extension\InstallRequirementsInterface;

class Requirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public function getRequirements(): array {
    $GLOBALS['install_requirements'] = 'install_requirements';

    return [];
  }

}
