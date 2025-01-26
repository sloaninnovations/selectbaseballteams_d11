<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\PluginBase as ComponentPluginBase;
use Drupal\Core\DependencyInjection\AutowireArgumentsTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for plugins supporting metadata inspection and translation.
 *
 * @ingroup plugin_api
 */
abstract class PluginBase extends ComponentPluginBase {

  use AutowireArgumentsTrait;
  use StringTranslationTrait;
  use DependencySerializationTrait;
  use MessengerTrait;

  /**
   * Instantiates a new instance of the implementing class using autowiring.
   *
   * @see \Drupal\Core\Plugin\ContainerFactoryPluginInterface
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return static::autowireArguments($container, $configuration, $plugin_id, $plugin_definition);
  }

}
