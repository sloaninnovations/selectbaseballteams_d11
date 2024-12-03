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
   * Tests that hook_runtime_requirements is loaded in SystemManager.
   */
  public function testRuntimeRequirements(): void {
    // Enable the test module.
    \Drupal::service('module_installer')->install(['module_runtime_requirements']);
    $testRequirements = [
      'title' => t('RuntimeError'),
      'value' => t('None'),
      'description' => t("Runtime Error."),
      'severity' => REQUIREMENT_ERROR,
    ];
    $requirements = \Drupal::service('system.manager')->listRequirements()['test.runtime.error'];
    $this->assertEquals($testRequirements, $requirements);
  }

  /**
   * Tests that hook_runtime_requirements_alter is loaded in SystemManager.
   */
  public function testRuntimeRequirementsAlter(): void {
    // Enable the test module.
    \Drupal::service('module_installer')->install(['module_runtime_requirements']);
    $testRequirementsAlter = [
      'title' => t('RuntimeWarning'),
      'value' => t('None'),
      'description' => t("Runtime Warning."),
      'severity' => REQUIREMENT_WARNING,
    ];
    $requirements = \Drupal::service('system.manager')->listRequirements()['test.runtime.error.alter'];
    $this->assertEquals($testRequirementsAlter, $requirements);
  }

}
