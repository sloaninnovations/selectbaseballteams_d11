<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin\Attribute;

/**
 * Defines a custom PluginExampleWithObjectDefinition attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class PluginExampleWithObjectDefinition extends PluginExample {

  /**
   * {@inheritdoc}
   */
  public function get(): array|object {
    $values = parent::get();
    $object = new \stdClass();
    foreach ($values as $property => $value) {
      $object->$property = $value;
    }
    return $object;
  }

}
