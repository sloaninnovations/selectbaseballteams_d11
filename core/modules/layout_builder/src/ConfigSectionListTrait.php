<?php

namespace Drupal\layout_builder;

use Drupal\Core\Config\Action\Attribute\ActionMethod;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a trait for maintaining a list of sections in config.
 *
 * @see \Drupal\layout_builder\SectionListInterface
 */
trait ConfigSectionListTrait {

  abstract public function count(): int;

  abstract public function getSection($delta);

  abstract protected function setSection($delta, Section $section);

  abstract protected function setSections(array $sections);

  abstract protected function uuidGenerator();

  /**
   * Adds a component to a given section.
   *
   * @param int $section
   *   The section delta.
   * @param int $position
   *   The position index inside that section.
   * @param mixed $value
   *   The component configuration data, including uuid, region, default_region and
   *   additional info if any.
   *
   * @return $this
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Add component'))]
  public function addComponent($section, int $position, array $value): static {
    if ($section >= $this->count()) {
      throw new ConfigActionException("Cannot use that section, as that delta can't be found.");
    }
    $sectionObject = $this->getSection($section);
    $configuration = $value;
    if (array_key_exists('region', $value) && is_array($value['region'])) {
      // Since the recipe author might not know ahead of time what layout the
      // section is using, they should supply a map whose keys are layout ids
      // and values are region names, so we know where to place this component.
      // If the section layout id is not in the map, they should supply the
      // name of a fallback region. If all that fails, give up with an
      // exception.
      $value['region'] = $value['region'][$sectionObject->getLayoutId()] ??
        $value['default_region'] ??
        throw new ConfigActionException("Cannot determine which region of the section to place this component into, because no default region was provided.");
    }
    if (!array_key_exists('uuid', $value)) {
      $value += ['uuid' => $this->uuidGenerator()->generate()];
    }
    // If no weight is given, there will be a warning.
    // Set a default, this will be overridden in insertComponent anyway.
    if (!array_key_exists('weight', $value)) {
      $value += ['weight' => 0];
    }

    // If the position is higher than the number of components, just put it last
    // instead of failing.
    $countComponentsInRegion = count($sectionObject->getComponentsByRegion($value['region']));
    if ($position > $countComponentsInRegion) {
      $position = $countComponentsInRegion;
    }
    $additional = $value['additional'] ?? [];
    unset($configuration['uuid']);
    unset($configuration['default_region']);
    unset($configuration['region']);
    unset($configuration['additional']);

    $component = [
      'uuid' => $value['uuid'],
      'region' => $value['region'],
      'configuration' => $configuration,
      'additional' => $additional,
    ];
    $sectionComponent = SectionComponent::fromArray($component);
    $sectionObject->insertComponent($position, $sectionComponent);
    $this->setSection($section, $sectionObject);
    return $this;
  }

}
