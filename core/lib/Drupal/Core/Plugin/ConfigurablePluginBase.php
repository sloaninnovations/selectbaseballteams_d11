<?php

declare(strict_types=1);

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;

/**
 * Base class for plugins that are configurable.
 *
 * Provides boilerplate methods for implementing
 * Drupal\Component\Plugin\ConfigurableInterface. Configurable plugins may
 * extend this base class, or use \Drupal\Component\Plugin\ConfigurableTrait
 * directly.
 */
abstract class ConfigurablePluginBase extends PluginBase implements ConfigurableInterface {
  use ConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

}
