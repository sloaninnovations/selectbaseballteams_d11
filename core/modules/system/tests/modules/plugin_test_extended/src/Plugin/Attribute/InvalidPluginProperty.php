<?php

declare(strict_types=1);

namespace Drupal\plugin_test_extended\Plugin\Attribute;

use Drupal\Core\Plugin\Attribute\PluginProperty;

/**
 * Example of an invalid plugin property because it is in a module.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class InvalidPluginProperty extends PluginProperty {}
