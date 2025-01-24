<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests legacy system functions.
 *
 * @group system
 * @group legacy
 */
class LegacySystemTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests system_check_directory deprecation.
   */
  public function testSystemCheckDirectory(): void {
    $this->expectDeprecation('system_check_directory() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Instead, your form validation should check if the directory exists and is writable. See https://www.drupal.org/node/3073687');
    $form_element = [
      '#name' => $this->randomMachineName(),
      '#value' => 'public://',
    ];
    $form_state = new FormState();
    $this->assertNotNull(system_check_directory($form_element, $form_state));
  }

}
