<?php

declare(strict_types=1);

// cspell:ignore analyse

namespace Drupal\PHPStan\Tests;

use Drupal\PHPStan\Rules\ReturnTypeWillBeAdded;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * Tests ReturnTypeWillBeAdded rule.
 *
 * @extends \PHPStan\Testing\RuleTestCase<\Drupal\PHPStan\Rules\ReturnTypeWillBeAdded>
 */
class ReturnTypeWillBeAddedTest extends RuleTestCase {

  /**
   * {@inheritdoc}
   */
  protected function getRule(): Rule {
    return new ReturnTypeWillBeAdded(
      self::getContainer()->getByType(FileTypeMapper::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function testRule(): void {
    include_once __DIR__ . '/../fixtures/return-type-will-be-added.php';
    $this->analyse(
      [__DIR__ . '/../fixtures/return-type-will-be-added.php'],
      [
        [
          "Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\FooInterface::fooInterfaceDuplicateAnnotation() '@return-type-will-be-added' annotation should only be defined once. Found 2 instances.",
          34,
        ],
        [
          "Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\FooInterface::fooInterfaceNativeReturnTypeExistsAlready() specifies a '@return-type-will-be-added' annotation, but already has a 'bool' native return type.",
          40,
        ],
        [
          "Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\FooInterface::fooInterfaceMissingPhpDocReturnType() specifies a '@return-type-will-be-added' annotation, but a '@return' annotation is missing.",
          45,
        ],
        [
          "Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\FooInterface::fooInterfaceValidAnnotationForAbstractClassImplementation() will add 'bool' as a native return type declaration in version 42.0.0. Add the return type to the implementation now.",
          81,
        ],
        [
          "Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\FooAbstract::fooAbstractDuplicateAnnotation() '@return-type-will-be-added' annotation should only be defined once. Found 2 instances.",
          117,
        ],
        [
          "Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\FooAbstract::fooAbstractNativeReturnTypeExistsAlready() specifies a '@return-type-will-be-added' annotation, but already has a 'bool' native return type.",
          125,
        ],
        [
          "Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\FooAbstract::fooAbstractMissingPhpDocReturnType() specifies a '@return-type-will-be-added' annotation, but a '@return' annotation is missing.",
          132,
        ],
        [
          "Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\FooInterface::fooInterfaceValidAnnotationForConcreteClassImplementation() will add 'bool' as a native return type declaration in version 42.0.0. Add the return type to the implementation now.",
          145,
        ],
        [
          "Declaration of Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\Foo::fooInterfaceValidAnnotationForConcreteClassImplementationOfWrongType(): string must be compatible with Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\FooInterface::fooInterfaceValidAnnotationForConcreteClassImplementationOfWrongType(): bool that will be added as a native return type declaration in version 42.0.0. Change the return type to the implementation now.",
          149,
        ],
        [
          "Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\FooTrait::fooTraitValidAnnotationForUseInAbstractClassOverriddenInConcreteClass() will add 'bool' as a native return type declaration in the future. Add the return type to the implementation now.",
          153,
        ],
        [
          "Declaration of Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\Foo::fooTraitValidAnnotationForUseInAbstractClassOverriddenInConcreteClassWithNativeTypeSpecified(): string must be compatible with Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\FooTrait::fooTraitValidAnnotationForUseInAbstractClassOverriddenInConcreteClassWithNativeTypeSpecified(): bool that will be added as a native return type declaration in the future. Change the return type to the implementation now.",
          157,
        ],
        [
          "Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\FooAbstract::fooAbstractValidAnnotation() will add 'bool' as a native return type declaration in version 42.0.0. Add the return type to the implementation now.",
          161,
        ],
        [
          "Drupal\\PHPStanTestFixture\\ReturnTypeWillBeAdded\\Foo::fooMissingPhpDocReturnType() specifies a '@return-type-will-be-added' annotation, but a '@return' annotation is missing.",
          168,
        ],
      ],
    );
  }

}
