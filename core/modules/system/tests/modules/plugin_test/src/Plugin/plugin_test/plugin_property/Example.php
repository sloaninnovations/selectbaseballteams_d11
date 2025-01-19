<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin\plugin_test\plugin_property;

use Drupal\Core\Plugin\Attribute\PluginProperty;
use Drupal\plugin_test\Plugin\Attribute\PluginExample;

#[PluginExample(
  id: 'example_with_plugin_property',
  custom: 'Example with Plugin Property',
)]
#[PluginProperty(
  key: 'core_plugin_property',
  value: 'core plugin property value',
)]
#[PluginProperty(
  key: 'plugin_test_extended_plugin_property',
  value: 'plugin_test_extended plugin property value',
  moduleDependencies: ['plugin_test_extended'],
)]
class Example {}
