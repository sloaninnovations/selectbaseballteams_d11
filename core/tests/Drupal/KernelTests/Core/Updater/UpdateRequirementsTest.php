<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Updater;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Update Requirements Hook.
 *
 * @group Hooks
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

  /**
   * Tests hook_update_requirements_alter.
   */
  public function testUpdateRequirementsAlter(): void {
    require_once 'core/includes/update.inc';

    \Drupal::service('module_installer')->install(['module_update_requirements']);
    $testRequirements = [
      'title' => t('UpdateWarning'),
      'value' => t('None'),
      'description' => t("Update Warning."),
      'severity' => REQUIREMENT_WARNING,
    ];
    $requirements = update_check_requirements()['test.update.error.alter'];
    $this->assertEquals($testRequirements, $requirements);
  }

}
