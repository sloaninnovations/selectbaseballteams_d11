<?php

declare(strict_types=1);

namespace Drupal\Component\Utility;

/**
 * Interface for a parser of a class file to find dependencies.
 */
interface ClassDependenciesParserInterface {

  /**
   * Determines if the class has the provided class attribute.
   *
   * @param string $attribute
   *   The fully qualified attribute to check for.
   *
   * @return bool
   *   TRUE if class has the attribute, FALSE if not.
   */
  public function hasClassAttribute(string $attribute): bool;

  /**
   * Get the dependencies for the class.
   *
   * @return string[][]
   *   The dependencies for the class, if any, as a two-dimensional array of
   *   dependency names indexed by the type, such as 'class' or 'interface' or
   *   'trait'.
   */
  public function getClassDependencies(): array;

  /**
   * Whether any of the dependencies in the list are missing.
   *
   * @param string[][] $dependencies
   *   A two-dimensional array of dependency names indexed by the type, such as
   *   'class' or 'interface' or 'trait'.
   *
   * @return bool
   *   TRUE if any of the dependencies are not found.
   */
  public static function hasMissingDependencies(array $dependencies): bool;

}
