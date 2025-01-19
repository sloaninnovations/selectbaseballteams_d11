<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin;

use Drupal\Component\Plugin\Attribute\PluginPropertyInterface;
use Drupal\Component\Utility\NestedArray;

class PluginPropertyExampleCallback {

  /**
   * Example of callback to add plugin property values to an object definition.
   *
   * @param \Drupal\Component\Plugin\Attribute\PluginPropertyInterface $attribute
   *   The attribute with the property to set.
   * @param array|object $definition
   *   The plugin definition.
   *
   * @return array|object
   *   The plugin definition.
   */
  public static function addToDefinition(PluginPropertyInterface $attribute, array|object $definition): array|object {
    if (!($definition instanceof \stdClass)) {
      return $definition;
    }

    $key = $attribute->getKey();
    $key = is_array($key) ? $key : [$key];
    $outerKey = reset($key);
    $nested = count($key) > 1;

    if ($nested) {
      $values = [];
      $subKey = array_slice($key, 1);
      NestedArray::setValue($values, $subKey, $attribute->getValue());
      $definition->$outerKey = $values;
      return $definition;
    }

    $definition->$outerKey = $attribute->getValue();
    return $definition;
  }

}
