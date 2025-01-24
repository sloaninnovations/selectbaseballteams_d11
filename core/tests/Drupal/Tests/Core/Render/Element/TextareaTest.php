<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textarea;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element\Textarea
 * @group Render
 */
class TextareaTest extends UnitTestCase {

  /**
   * @covers ::valueCallback
   *
   * @dataProvider providerTestValueCallback
   */
  public function testValueCallback($expected, $input, array $element = []): void {
    $form_state = $this->prophesize(FormStateInterface::class)->reveal();
    $this->assertSame($expected, Textarea::valueCallback($element, $input, $form_state));
  }

  /**
   * Data provider for testValueCallback().
   */
  public static function providerTestValueCallback() {
    return [
      'False' => [
        NULL,
        FALSE,
        ['#normalize_newlines' => TRUE],
      ],
      'NULL' => [
        NULL,
        NULL,
        ['#normalize_newlines' => TRUE],
      ],
      'empty array' => [
        '',
        ['test'],
        ['#normalize_newlines' => TRUE],
      ],
      'string' => [
        'test',
        'test',
        ['#normalize_newlines' => TRUE],
      ],
      'integer' => [
        '123',
        123,
        ['#normalize_newlines' => TRUE],
      ],
      'normalize new lines' => [
        "some\ndifferent\nline\nendings",
        "some\r\ndifferent\rline\nendings",
        ['#normalize_newlines' => TRUE],
      ],
      'do not normalize new lines' => [
        "some\r\ndifferent\rline\nendings",
        "some\r\ndifferent\rline\nendings",
        ['#normalize_newlines' => FALSE],
      ],
    ];
  }

}
