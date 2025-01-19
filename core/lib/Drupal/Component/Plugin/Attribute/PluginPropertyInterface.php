<?php

namespace Drupal\Component\Plugin\Attribute;

/**
 * Interface for attributes that provide properties to plugin definitions.
 */
interface PluginPropertyInterface extends AttributeInterface {

  /**
   * Returns the key within the plugin definition of the property to set.
   *
   * If the key is an array, it will be treated as a nested key, with the
   * outermost keys coming first.
   *
   * @return int|string|array
   */
  public function getKey(): int|string|array;

  /**
   * Returns the value to be set for the plugin definition property.
   *
   * @return mixed
   */
  public function getValue(): mixed;

  /**
   * Checks whether the property attribute can be applied to a plugin attribute.
   *
   * @param class-string $plugin_class
   *   The class of the plugin attribute.
   *
   * @return bool
   *   TRUE if no restriction on allowed plugin classes or plugin class is an
   *   instance of the allowed plugin classes.
   */
  public function isValidPluginClass(string $plugin_class): bool;

  /**
   * Adds the property value to the plugin definition.
   *
   * @param array|object $definition
   *   The plugin attribute data retrieved from Plugin::get().
   *
   * @return array|object
   *   The plugin definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function addToDefinition(array|object $definition): array|object;

  /**
   * Whether the attribute has any dependencies.
   *
   * @return bool
   */
  public function hasDependencies(): bool;

  /**
   * Whether the attribute has any missing dependencies.
   *
   * @return bool
   */
  public function hasMissingDependencies(): bool;

}
