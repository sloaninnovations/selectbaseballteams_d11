<?php

// phpcs:ignoreFile

declare(strict_types=1);

namespace Drupal\PHPStanTestFixture\ReturnTypeWillBeAdded;

interface FooInterface {

  /**
   * @return-type-will-be-added in version 42.0.0
   * @return bool
   */
  public function fooInterfaceValidAnnotationForAbstractClassImplementation();

  /**
   * @return-type-will-be-added in version 42.0.0
   * @return bool
   */
  public function fooInterfaceValidAnnotationForConcreteClassImplementation();

  /**
   * @return-type-will-be-added in version 42.0.0
   * @return bool
   */
  public function fooInterfaceValidAnnotationForConcreteClassImplementationOfWrongType();

  /**
   * @return-type-will-be-added in version 42.0.0
   * @return-type-will-be-added in version 43.0.0
   * @return bool
   */
  public function fooInterfaceDuplicateAnnotation();

  /**
   * @return-type-will-be-added in version 42.0.0
   * @return bool
   */
  public function fooInterfaceNativeReturnTypeExistsAlready(): bool;

  /**
   * @return-type-will-be-added in version 42.0.0
   */
  public function fooInterfaceMissingPhpDocReturnType();

}

trait FooTrait {

  /**
   * @return-type-will-be-added
   * @return bool
   */
  protected function fooTraitValidAnnotationForUseInAbstractClassOverriddenInConcreteClass() {
    return TRUE;
  }

  /**
   * @return-type-will-be-added
   * @return bool
   */
  protected function fooTraitValidAnnotationForUseInAbstractClassOverriddenInConcreteClassWithNativeTypeSpecified() {
    return TRUE;
  }

  /**
   * @return-type-will-be-added in version 42.0.0
   * @return bool
   */
  protected function fooTraitValidAnnotation() {
    return TRUE;
  }

}

abstract class FooAbstract implements FooInterface {

  use FooTrait;

  public function fooInterfaceValidAnnotationForAbstractClassImplementation() {
    return TRUE;
  }

  /**
   * Only implemented to respect inheritance.
   */
  public function fooInterfaceDuplicateAnnotation() {
    return TRUE;
  }

  /**
   * Only implemented to respect inheritance.
   */
  public function fooInterfaceNativeReturnTypeExistsAlready(): bool {
    return TRUE;
  }

  /**
   * Only implemented to respect inheritance.
   */
  public function fooInterfaceMissingPhpDocReturnType() {
    return TRUE;
  }

  /**
   * @return-type-will-be-added in version 42.0.0
   * @return bool
   */
  abstract public function fooAbstractValidAnnotation();

  /**
   * @return-type-will-be-added in version 42.0.0
   * @return-type-will-be-added in version 43.0.0
   * @return bool
   */
  public function fooAbstractDuplicateAnnotation() {
    return TRUE;
  }

  /**
   * @return-type-will-be-added in version 42.0.0
   * @return bool
   */
  public function fooAbstractNativeReturnTypeExistsAlready(): bool {
    return TRUE;
  }

  /**
   * @return-type-will-be-added in version 42.0.0
   */
  public function fooAbstractMissingPhpDocReturnType() {
    return TRUE;
  }

}

class Foo extends FooAbstract {

  public function __construct(
    private readonly ?int $baz,
  ) {
  }

  public function fooInterfaceValidAnnotationForConcreteClassImplementation() {
    return TRUE;
  }

  public function fooInterfaceValidAnnotationForConcreteClassImplementationOfWrongType(): string {
    return 'bar';
  }

  protected function fooTraitValidAnnotationForUseInAbstractClassOverriddenInConcreteClass() {
    return TRUE;
  }

  protected function fooTraitValidAnnotationForUseInAbstractClassOverriddenInConcreteClassWithNativeTypeSpecified(): string {
    return 'baz';
  }

  public function fooAbstractValidAnnotation() {
    return TRUE;
  }

  /**
   * @return-type-will-be-added in version 42.0.0
   */
  public function fooMissingPhpDocReturnType() {
    return TRUE;
  }

}
