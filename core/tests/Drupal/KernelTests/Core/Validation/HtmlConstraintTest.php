<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Test class for Html constraint.
 *
 * @group Validation
 */
class HtmlConstraintTest extends KernelTestBase {

  /**
   * Tests regex values.
   *
   * @param string $html
   *   The html string to test
   * @param array $errors
   *   An array of errors to expect.
   * @param bool $document
   *   True if this is a full html document, false if it is a fragment.
   *
   * @dataProvider htmlConstraintDataProvider
   */
  public function testHtmlConstraint(string $html, array $errors, bool $document = FALSE): void {
    $definition = DataDefinition::create('string');
    match (TRUE) {
      $document => $definition->addConstraint('Html5', ['mode' => 'document']),
      default => $definition->addConstraint('Html5'),
    };
    $testString = $this->container->get('typed_data_manager')->create($definition);
    $testString->setValue($html);
    $violations = $testString->validate();
    $this->assertCount(count($errors), $violations);
    foreach ($violations as $violation) {
      $this->assertTrue(in_array((string) $violation->getMessage(), $errors, TRUE));
    }

  }

  /**
   * Tests that an InvalidArgumentException is thrown on a bad parsing mode.
   */
  public function testHtmlConstraintArguments(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid HTML parsing mode. the `mode` argument must be "fragment" or "document".');
    $definition = DataDefinition::create('string');
    $definition->addConstraint('Html5', ['mode' => 'unsupported']);
    $testString = $this->container->get('typed_data_manager')->create($definition);
    $testString->setValue("<p>Test</p>");
    $testString->validate();
  }

  /**
   * Provides data for testValidRegexConstraint().
   *
   * @return array[]
   *   The test cases.
   */
  public static function htmlConstraintDataProvider(): array {
    return [
      'valid_document' => [
        'html' => "<!doctype html>\n<html>\n<head></head><body><p>test</p></body></html>",
        'errors' => [],
        'document' => TRUE,
      ],
      'invalid_document' => [
        'html' => "<!doctype html>\n<html>\n<head></head><body><p>test</a></body></html>",
        'errors' => ['Line 0, Col 0: Could not find closing tag for a'],
        'document' => TRUE,
      ],
      'valid_document_parsed_as_fragment' => [
        'html' => "<!doctype html>\n<html>\n<head></head><body><p>test</p></body></html>",
        'errors' => ['Line 0, Col 0: Illegal placement of DOCTYPE tag. Ignoring: html'],
      ],
      'valid_fragment' => [
        'html' => '<p>test</p>',
        'errors' => [],
      ],
      'invalid_fragment' => [
        'html' => '<p>test</a>',
        'errors' => ['Line 0, Col 0: Could not find closing tag for a'],
      ],
      'valid_fragment_parsed_as_document'  => [
        'html' => '<p>test</p>',
        'errors' => ['Line 0, Col 0: No DOCTYPE specified.'],
        'document' => TRUE,
      ],
    ];
  }

}
