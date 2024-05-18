<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests ValidRegexConstraint.
 *
 * @group Validation
 */
class ValidRegexConstraintTest extends KernelTestBase {

  /**
   * Typed string data to test.
   *
   * @var \Drupal\Core\TypedData\Plugin\DataType\StringData
   */
  protected StringData $testString;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $definition = DataDefinition::create('string')
      ->addConstraint('ValidRegex');
    $this->testString = $this->container->get('typed_data_manager')->create($definition);
  }

  /**
   * Tests regex values.
   *
   * @param string $regex
   *   The value to test
   * @param string|null $message
   *   The expected error message, if any.
   *
   * @dataProvider validRegexConstraintProvider
   */
  public function testValidRegexConstraint(string $regex, ?string $message = NULL): void {
    $this->testString->setValue($regex);
    $violations = $this->testString->validate();
    if (!$message) {
      $this->assertCount(0, $violations);
      return;
    }
    $this->assertCount(1, $violations);
    $this->assertSame($message, (string) $violations->get(0)->getMessage());

  }

  /**
   * Provides data for testValidRegexConstraint().
   *
   * @return array[]
   *   The test cases.
   */
  public static function validRegexConstraintProvider(): array {
    return [
      'invalid no ending delimiter' => [
        'regex' => '/test',
        'message' => 'The value "/test" is not valid: Internal error.',
      ],
      'invalid bad character class' => [
        'regex' => '%[0-9%',
        'message' => 'The value "%[0-9%" is not valid: Internal error.',
      ],
      'invalid no delimiters' => [
        'regex' => 'no_delimiters',
        'message' => 'The value "no_delimiters" is not valid: Internal error.',
      ],
      'valid simple regex' => [
        'regex' => '/test/',
      ],
      'valid complex regex' => [
        'regex' => '%^<\s*(/\s*)?([a-zA-Z0-9\-]+)\s*([^>]*)>?|(<!--.*?-->)$%',
      ],
      'valid with hashtag delimiters' => [
        'regex' => '#[0-9].*#',
      ],
    ];
  }

}
