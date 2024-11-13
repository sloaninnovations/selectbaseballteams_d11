<?php

declare(strict_types=1);

namespace Drupal\layout_builder\Plugin\ConfigAction;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\layout_builder\Plugin\ConfigAction\Deriver\ConfigLayoutBuilderDeriver;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'add_layout_component',
  deriver: ConfigLayoutBuilderDeriver::class,
)]
final class AddComponent implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    private readonly ConfigManagerInterface $configManager,
    private readonly UuidInterface $uuidGenerator,
    private readonly string $pluginId,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    assert(is_array($plugin_definition));
    return new static(
      $container->get(ConfigManagerInterface::class),
      $container->get('uuid'),
      $plugin_id,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    $section = $value['section'];
    $position = $value['position'];

    $entity = $this->configManager->loadConfigEntityByName($configName);
    assert($entity instanceof SectionListInterface);

    if ($section >= $entity->count()) {
      throw new ConfigActionException("Cannot use that section, as that delta can't be found.");
    }
    $sectionObject = $entity->getSection($section);
    $configuration = $value['component'];
    if (array_key_exists('region', $configuration) && is_array($configuration['region'])) {
      // Since the recipe author might not know ahead of time what layout the
      // section is using, they should supply a map whose keys are layout ids
      // and values are region names, so we know where to place this component.
      // If the section layout id is not in the map, they should supply the
      // name of a fallback region. If all that fails, give up with an
      // exception.
      $value['region'] = $configuration['region'][$sectionObject->getLayoutId()] ??
        $configuration['default_region'] ??
        throw new ConfigActionException("Cannot determine which region of the section to place this component into, because no default region was provided.");
    }
    $value += ['uuid' => $this->uuidGenerator->generate()];
    // If no weight is given, there will be a warning.
    // Set a default, this will be overridden in insertComponent anyway.
    $value += ['weight' => 0];

    // If the position is higher than the number of components, just put it last
    // instead of failing.
    $countComponentsInRegion = count($sectionObject->getComponentsByRegion($value['region']));
    if ($position > $countComponentsInRegion) {
      $position = $countComponentsInRegion;
    }
    $additional = $value['additional'] ?? [];
    unset($configuration['section']);
    unset($configuration['position']);
    unset($configuration['uuid']);
    unset($configuration['default_region']);
    unset($configuration['region']);
    unset($configuration['additional']);

    $component = [
      'uuid' => $value['uuid'],
      'region' => $value['region'],
      'weight' => $value['weight'],
      'configuration' => $configuration,
      'additional' => $additional,
    ];
    $sectionComponent = SectionComponent::fromArray($component);
    $sectionObject->insertComponent($position, $sectionComponent);
    $entity->setSection($section, $sectionObject);
    $entity->save();
  }

}
