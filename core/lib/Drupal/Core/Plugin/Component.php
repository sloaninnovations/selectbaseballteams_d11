<?php

namespace Drupal\Core\Plugin;

use Drupal\Core\Theme\Component\ComponentMetadata;
use Drupal\Core\Render\Component\Exception\InvalidComponentException;

/**
 * Simple value object that contains information about the component.
 */
class Component extends PluginBase {

  public const TEMPLATE_VARIANT_SEPARATOR = '--';

  /**
   * The component's metadata.
   *
   * @var \Drupal\Core\Theme\Component\ComponentMetadata
   */
  public readonly ComponentMetadata $metadata;

  /**
   * The component machine name.
   *
   * @var string
   */
  public readonly string $machineName;

  /**
   * The library definition to be attached with the component.
   *
   * @var array
   */
  public readonly array $library;

  /**
   * The templates to be rendered with the component.
   *
   * @var array
   */
  public readonly array $templates;

  /**
   * Component constructor.
   *
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (str_contains($plugin_id, '/')) {
      $message = sprintf('Component ID cannot contain slashes: %s', $plugin_id);
      throw new InvalidComponentException($message);
    }
    $template = $plugin_definition['template'] ?? NULL;
    if (!$template) {
      $message = sprintf(
        'Unable to find the Twig template for the component "%s".',
        $plugin_id
      );
      throw new InvalidComponentException($message);
    }
    $this->machineName = $plugin_definition['machineName'];
    $this->library = $plugin_definition['library'] ?? [];
    $this->metadata = new ComponentMetadata(
      $plugin_definition,
      $configuration['app_root'],
      (bool) ($configuration['enforce_schemas'] ?? FALSE)
    );
    $templates = [$template];
    if (isset($plugin_definition['variants'])) {
      foreach ($plugin_definition['variants'] as $variant) {
        $templates[$variant] = $this->machineName . self::TEMPLATE_VARIANT_SEPARATOR . $variant . '.twig';
      }
    }
    $this->templates = $templates;
  }

  /**
   * The template path.
   *
   * @return string|null
   *   The path to the template.
   */
  public function getTemplatePath($variant = NULL): ?string {
    return $this->metadata->path . DIRECTORY_SEPARATOR . $this->getTemplate($variant);
  }

  public function __get(string $name) {
    if ($name === 'template') {
      return $this->getTemplate('');
    }
    return $this->{$name};
  }

  public function getTemplate($variant): ?string {
    return !empty($variant) ? $this->templates[$variant] : $this->templates[0] ;
  }

  /**
   * The auto-computed library name.
   *
   * @return string
   *   The library name.
   */
  public function getLibraryName(): string {
    $library_id = $this->getPluginId();
    $library_id = str_replace(':', '--', $library_id);
    return sprintf('core/components.%s', $library_id);
  }

}
