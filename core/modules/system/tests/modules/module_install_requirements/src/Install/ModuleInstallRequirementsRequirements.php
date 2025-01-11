<?php

declare(strict_types=1);

namespace Drupal\module_install_requirements\Install;

use Drupal\Core\Extension\InstallRequirementsInterface;

class ModuleInstallRequirementsRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    $GLOBALS['module_install_requirements'] = 'module_install_requirements';

    return [];
  }

}
