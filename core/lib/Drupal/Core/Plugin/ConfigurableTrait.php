<?php

declare(strict_types=1);

namespace Drupal\Core\Plugin;

use Drupal\Component\Utility\NestedArray;

/**
 * Implementation class for \Drupal\Component\Plugin\ConfigurableInterface.
 *
 * In order for configurable plugins to maintain their configuration, the
 * default configuration must be merged into any explicitly defined
 * configuration. This trait provides the appropriate getters and setters to
 * handle this logic, removing the need for excess boilerplate.
 *
 * If you use this trait, you must also implement ConfigurableInterface in your
 * class and call setConfiguration() in your constructor after calling the
 * parent constructor in order to merge the default configuration into the
 * plugin's provided configuration.
 *
 * @ingroup Plugin
 */
trait ConfigurableTrait {

  /**
   * Configuration information passed into the plugin.
   *
   * This property is declared in \Drupal\Component\Plugin\PluginBase as well,
   * which most classes using this trait will ultimately be extending. It is
   * re-declared here to make the trait self-contained and to permit use of the
   * trait in classes that do not extend PluginBase. Re-declaring a class
   * property in a trait is permitted since php 7.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Gets this plugin's configuration.
   *
   * @return array
   *   An array of this plugin's configuration.
   *
   * @see \Drupal\Component\Plugin\ConfigurableInterface::getConfiguration()
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Sets the configuration for this plugin instance.
   *
   * The provided configuration is merged with the default configuration and
   * stored in the plugin's $configuration member. If a configuration key exists
   * in both, then the provided configuration will override the default.
   *
   * @param array $configuration
   *   An associative array containing the plugin's configuration. Provided
   *   value is merged with default configuration.
   *
   * @return $this
   *
   * @see \Drupal\Component\Plugin\ConfigurableInterface::setConfiguration()
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeepArray([$this->defaultConfiguration(), $configuration], TRUE);
    return $this;
  }

  /**
   * Gets default configuration for this plugin.
   *
   * @return array
   *   An associative array with the default configuration.
   *
   * @see \Drupal\Component\Plugin\ConfigurableInterface::defaultConfiguration()
   */
  public function defaultConfiguration() {
    return [];
  }

}
