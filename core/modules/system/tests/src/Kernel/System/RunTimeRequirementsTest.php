<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\System;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the effectiveness of hook_runtime_requirements().
 *
 * @group system
 */
class RunTimeRequirementsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests hook_runtime_requirements() and hook_runtime_requirements_alter().
   */
  public function testRuntimeRequirements(): void {
    // Enable the test module.
    \Drupal::service('module_installer')->install(['module_runtime_requirements']);
    $testRequirements = [
      'title' => t('RuntimeError'),
      'value' => t('None'),
      'description' => t('Runtime Error.'),
      'severity' => REQUIREMENT_ERROR,
    ];
    $requirements = \Drupal::service('system.manager')->listRequirements()['test.runtime.error'];
    $this->assertEquals($testRequirements, $requirements);

    $testRequirementsAlter = [
      'title' => t('RuntimeWarning'),
      'value' => t('None'),
      'description' => t('Runtime Warning.'),
      'severity' => REQUIREMENT_WARNING,
    ];
    $requirementsAlter = \Drupal::service('system.manager')->listRequirements()['test.runtime.error.alter'];
    $this->assertEquals($testRequirementsAlter, $requirementsAlter);
  }

}
