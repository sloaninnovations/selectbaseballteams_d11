<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Icon;

use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface for icon_extractor plugins.
 *
 * @internal
 *   This API is experimental.
 */
interface IconExtractorInterface extends PluginFormInterface {

  /**
   * Get a list of all the icons discovered by this extractor.
   *
   * The icons must be provided as an associative array with values equal to the
   * icon definition (IconDefinitionInterface).
   * To ease the creation of IconDefinition, createIcon() method is provided.
   *
   * @return \Drupal\Core\Theme\Icon\IconDefinitionInterface[]
   *   Gets a built list of icons that are found by this extractor. Array values
   *   are the icon definition for each of the icons discovered.
   */
  public function discoverIcons(): array;

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

  /**
   * Returns the translated plugin description.
   */
  public function description(): string;

  /**
   * Create the icon definition from an extractor plugin.
   *
   * @param string $icon_id
   *   The id of the icon.
   * @param string|null $source
   *   The source, url or path of the icon.
   * @param string|null $group
   *   The group of the icon.
   * @param array|null $data
   *   The icon data.
   *
   * @return \Drupal\Core\Theme\Icon\IconDefinitionInterface
   *   The Icon definition.
   *
   * @see \Drupal\Core\Theme\Icon\IconDefinition::create()
   */
  public function createIcon(string $icon_id, ?string $source = NULL, ?string $group = NULL, ?array $data = NULL): IconDefinitionInterface;

}
