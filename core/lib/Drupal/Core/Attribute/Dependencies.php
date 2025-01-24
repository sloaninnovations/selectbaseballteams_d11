<?php

declare(strict_types=1);

namespace Drupal\Core\Attribute;

/**
 * Defines a Dependencies attribute object.
 *
 * When PHP attributes are used for class discovery, whether for plugins, hooks,
 * or similar, reflection is used on the class files in order to extract the
 * attribute data. The class being reflected can depend on code  in modules other
 * than the dependencies declared in the .info.yml of the module the class
 * is defined in, with the intention that the class should be ignored by
 * discovery if these undeclared dependencies do not exist. For example, a
 * migrate source plugin class in the taxonomy can extend DrupalSqlBase, a class
 * in migrate_drupal.
 *
 * Reflection used on these classes, when those dependencies are missing or
 * uninstalled, can result in fatal errors or exceptions being thrown. In order
 * to prevent these errors, this attribute can be added to the class to declare
 * module dependencies explicitly.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Dependencies {

  /**
   * Constructs a dependencies attribute object.
   *
   * @param string[] $modules
   *   List of modules that the class depends on. The module the plugin class is
   *   in and modules listed in the module's .info.yml dependencies do not need
   *   to be listed here. Also, if the class is implementing a plugin, the
   *   module defining the plugin type does not need to be included, either.
   *   Note: this attribute is parsed statically by a method other than
   *   Reflection classes. The 'modules' argument should be set as an array of
   *   module machine names as string literals, and not references to class
   *   constants or other expressions.
   */
  public function __construct(protected readonly array $modules) {}

  /**
   * Gets the list of module dependencies.
   *
   * @return string []
   *   The list of module dependencies.
   */
  public function getModules(): array {
    return $this->modules;
  }

}
