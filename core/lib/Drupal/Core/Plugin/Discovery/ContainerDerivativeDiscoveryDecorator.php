<?php

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Injects dependencies into derivers if they use ContainerDeriverInterface.
 *
 * @see \Drupal\Core\Plugin\Discovery\ContainerDeriverInterface
 */
class ContainerDerivativeDiscoveryDecorator extends DerivativeDiscoveryDecorator {

  /**
   * Name of the alter hook if one should be invoked.
   *
   * @var string|null
   */
  protected $alterHook;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|null
   */
  protected $moduleHandler;

  /**
   * Creates a new instance.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated
   *   The parent object implementing DiscoveryInterface that is being
   *   decorated.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface|null $module_handler
   *   The module handler.
   * @param string|null $alter_hook
   *   Name of the alter hook if one should be invoked.
   */
  public function __construct(DiscoveryInterface $decorated, ModuleHandlerInterface $module_handler = NULL, $alter_hook = NULL) {
    parent::__construct($decorated);
    $this->moduleHandler = $module_handler;
    $this->alterHook = $alter_hook;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $plugin_definitions = $this->decorated->getDefinitions();
    if ($this->alterHook) {
      $this->moduleHandler->alter($this->alterHook . '_base_plugin_definitions', $plugin_definitions);
    }
    return $this->getDerivatives($plugin_definitions);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeriver($base_plugin_id, $base_definition) {
    if (!isset($this->derivers[$base_plugin_id])) {
      $this->derivers[$base_plugin_id] = FALSE;
      $class = $this->getDeriverClass($base_definition);
      if ($class) {
        // If the deriver provides a factory method, pass the container to it.
        if (is_subclass_of($class, '\Drupal\Core\Plugin\Discovery\ContainerDeriverInterface')) {
          /** @var \Drupal\Core\Plugin\Discovery\ContainerDeriverInterface $class */
          $this->derivers[$base_plugin_id] = $class::create(\Drupal::getContainer(), $base_plugin_id);
        }
        else {
          $this->derivers[$base_plugin_id] = new $class($base_plugin_id);
        }
      }
    }
    return $this->derivers[$base_plugin_id] ?: NULL;
  }

}
