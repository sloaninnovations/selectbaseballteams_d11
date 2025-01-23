<?php

namespace Drupal\Component\Plugin\Discovery;

use Drupal\Component\Plugin\Definition\DerivablePluginDefinitionInterface;
use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

trait DiscoveryCachedTrait {

  use DiscoveryTrait;

  /**
   * Cached definitions array.
   *
   * @var array
   */
  protected $definitions;

  protected $aliasDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    // Fetch definitions if they're not loaded yet.
    if (!isset($this->definitions)) {
      $this->getDefinitions();
    }

    try {
      return $this->doGetDefinition($this->definitions, $plugin_id, TRUE);
    }
    catch (PluginNotFoundException $e) {
      $aliases = $this->getAliasDefinitions();
      if (isset($aliases[$plugin_id])) {
        return $this->doGetDefinition($this->definitions, $aliases[$plugin_id], $exception_on_invalid);
      }

      return $exception_on_invalid ? throw $e : NULL;
    }
  }

  public function getAliasDefinitions(): array {
    if (isset($this->aliasDefinitions)) {
      return $this->aliasDefinitions;
    }

    $this->aliasDefinitions = [];

    $definitions = $this->getDefinitions();
    foreach ($definitions as $id => $definition) {
      if ($definition instanceof PluginDefinitionInterface) {
        if ($definition instanceof DerivablePluginDefinitionInterface && $definition->getDeriver()) {
          continue;
        }

        $this->aliasDefinitions[$definition->getClass()] = $definition->id();
        continue;
      }

      if ($definition['deriver'] ?? FALSE) {
        continue;
      }

      if ($definition['class'] ?? FALSE) {
        $this->aliasDefinitions[$definition['class']] = $definition['id'] ?? $id;
      }
    }

    return $this->aliasDefinitions;
  }

}
