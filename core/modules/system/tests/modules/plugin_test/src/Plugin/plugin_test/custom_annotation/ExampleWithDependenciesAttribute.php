<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin\plugin_test\custom_annotation;

use Drupal\Core\Attribute\Dependencies;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\plugin_test\Plugin\Attribute\PluginExample;

/**
 * This is used to test discovery on a class with the Dependencies attribute.
 */
#[PluginExample(
  id: 'example_with_dependencies_attribute',
  custom: 'Example with the Dependencies attribute.'
)]
#[Dependencies(['plugin_test_extended'])]
class ExampleWithDependenciesAttribute {

  #[TrustedCallback]
  public function testMethod(): void {}

}
