<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Unit;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ConfigFormBase class modifications.
 *
 * @group system
 */
class ConfigFormBaseTest extends UnitTestCase {

  /**
   * Tests the handling of text_format elements with #config_target.
   */
  public function testTextFormatConfigTarget() {
    // Create a mock for FormStateInterface.
    /** @var \Drupal\Core\Form\FormStateInterface|MockObject $form_state */
    $form_state = $this->createMock(FormStateInterface::class);

    // Define the expected key for the map.
    $expected_key = 'config_targets';

    // Define the expected value for the map.
    $expected_map = [
      'foo.settings.value' => ['parent', 'key', 'subkey'],
      'foo.settings.format' => ['parent', 'key', 'subkey'],
    ];

    // Prepare the form state mock to expect the set call.
    $form_state->expects($this->once())
      ->method('set')
      ->with(
            $this->equalTo($expected_key),
            $this->callback(function ($map) use ($expected_map) {
                // Check if the map contains the expected entries.
              foreach ($expected_map as $key => $expected_value) {
                if (!isset($map[$key]) || $map[$key] !== $expected_value) {
                    return FALSE;
                }
              }
                return TRUE;
            })
        );

    // Use reflection to access the protected method.
    $reflectedMethod = new \ReflectionMethod(
      ConfigFormBase::class,
      'doStoreConfigMap'
    );
    $reflectedMethod->setAccessible(TRUE);

    // Create an instance of ConfigFormBase to invoke the method on.
    $mockedInstance = $this->getMockBuilder(ConfigFormBase::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Define a sample form element.
    $element = [
      'parent' => [
        'key' => [
          'subkey' => [
            '#type' => 'text_format',
            '#title' => 'Subkey title',
            '#config_target' => 'foo.settings:key.subkey',
            '#array_parents' => ['parent', 'key', 'subkey'],
          ],
        ],
      ],
    ];

    // Invoke the method.
    $reflectedMethod->invokeArgs(
      $mockedInstance,
      [$element, $form_state]
    );

  }

}
