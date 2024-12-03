<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Updater;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Update Requirements Hook.
 */
class UpdateRequirementsTest extends KernelTestBase {

  /**
   * Tests hook_update_requirements.
   */
  public function testUpdateRequirements(): void {
    require_once 'core/includes/update.inc';

    \Drupal::service('module_installer')->install(['module_update_requirements']);
    $testRequirements = [
      'title' => t('UpdateError'),
      'value' => t('None'),
      'description' => t("Update Error."),
      'severity' => REQUIREMENT_ERROR,
    ];
    $requirements = update_check_requirements()['test.update.error'];
    $this->assertEquals($testRequirements, $requirements);
  }

}
