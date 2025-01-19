<?php

namespace Drupal\Core\Entity\Attribute;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Plugin\Attribute\PluginProperty;

/**
 * Attribute class to add form handler properties to entity type definition.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class EntityTypeProperty extends PluginProperty {

  public function __construct(
    int|string|array $key,
    mixed $value,
    array $moduleDependencies = [],
  ) {
    parent::__construct($key, $value, [EntityType::class], $moduleDependencies);
  }

  /**
   * {@inheritdoc}
   */
  public function addToDefinition(array|object $definition): array|object {
    if (!($definition instanceof EntityTypeInterface)) {
      throw new \InvalidArgumentException(sprintf('%s attribute can not be used with %s, because it is not an entity type definition.', static::class, $this->getClass()));
    }

    $value = $this->getValue();
    $key = $this->getKey();
    $key = is_array($key) ? $key : [$key];
    $outer_key = reset($key);
    $property = $definition->get($outer_key);

    // Key is nested if it is an array and has more than one item.
    $nested = is_array($key) && ((count($key) > 1));
    if (!$nested) {
      if (!is_null($property) && !is_array($property) && is_array($value)) {
        // Can not set an array value for a non-array property.
        throw new InvalidPluginDefinitionException($definition->id(), sprintf('Invalid property key %s specified for %s entity type definition in %s.', implode(', ', $key), $definition->id(), $this->getClass()));
      }
      return $definition->set($outer_key, $value);
    }

    if (!is_null($property) && !is_array($property)) {
      // Nested key is invalid if property exists and is not an array.
      throw new InvalidPluginDefinitionException($definition->id(), sprintf('Invalid property key %s specified for %s entity type definition in %s.', implode(', ', $key), $definition->id(), $this->getClass()));
    }
    $property = $property ?? [];
    $sub_key = array_slice($key, 1);
    NestedArray::setValue($property, $sub_key, $value);
    return $definition->set($outer_key, $property);
  }

}
