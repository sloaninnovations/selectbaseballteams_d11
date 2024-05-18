<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests ValidRegexConstraint.
 *
 * @group Validation
 */
class ValidRegexConstraintTest extends KernelTestBase {

  /**
   * Tests valid regex values.
   *
   * @param $value
   *   The value to test
   *
   * @dataProvider validRegexConstraintPassProvider
   *
   * @throws \Exception
   */
  public function testValidRegexConstraintPass($value) {
    $definition = DataDefinition::create('string')
      ->addConstraint('ValidRegex');
    $data = $this->container->get('typed_data_manager')->create($definition);
    $data->setValue($value);
    $this->assertCount(0, $data->validate());

  }

  /**
   * Provides data for testValidRegexConstraintPass().
   *
   * @return array[]
   *   The test cases.
   */
  public static function validRegexConstraintPassProvider(): array {
    return [
      ['value' => '/test/'],
      ['value' => '%^<\s*(/\s*)?([a-zA-Z0-9\-]+)\s*([^>]*)>?|(<!--.*?-->)$%'],
      ['value' => '#[0-9].*#'],
    ];
  }

  /**
   * Tests invalid regex values.
   *
   * @param string $value
   *   The value to test
   *
   * @dataProvider validRegexConstraintFailProvider
   *
   */
  public function testValidRegexConstraintFail(string $value): void {
    $definition = DataDefinition::create('string')
      ->addConstraint('ValidRegex');
    $data = $this->container->get('typed_data_manager')->create($definition);
    $data->setValue($value);
    $violations = $data->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("The string '" . $value . "' is not a valid regular expression.", (string) $violations->get(0)->getMessage());

  }

  /**
   * Provides data for testValidRegexConstraintFail().
   *
   * @return array[]
   *   The data.
   */
  public static function validRegexConstraintFailProvider(): array {
    return [
      'no ending delimiter' => ['/test'],
      'bad character class' => ['%[0-9%'],
      'no delimiters' => ['no_delimiters'],
    ];
  }

}
