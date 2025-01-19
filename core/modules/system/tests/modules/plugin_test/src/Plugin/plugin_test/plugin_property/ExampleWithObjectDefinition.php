<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin\plugin_test\plugin_property;

use Drupal\Core\Plugin\Attribute\PluginProperty;
use Drupal\plugin_test\Plugin\Attribute\PluginExampleWithObjectDefinition;
use Drupal\plugin_test\Plugin\PluginPropertyExampleCallback;

#[PluginExampleWithObjectDefinition(
  id: 'example_object_definition',
  custom: 'Example with Object Definition',
)]
#[PluginProperty(
  key: 'core_plugin_property_with_callback',
  value: 'core plugin property with callback value',
  addToDefinitionCallback: [PluginPropertyExampleCallback::class, 'addToDefinition'],
)]
#[PluginProperty(
  key: ['nested', 'key'],
  value: 'nested key',
  addToDefinitionCallback: [PluginPropertyExampleCallback::class, 'addToDefinition'],
)]
class ExampleWithObjectDefinition {}
