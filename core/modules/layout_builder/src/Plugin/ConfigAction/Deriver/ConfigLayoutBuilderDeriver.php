<?php

declare(strict_types=1);

namespace Drupal\layout_builder\Plugin\ConfigAction\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\layout_builder\SectionListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 *   This API is experimental.
 */
final class ConfigLayoutBuilderDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id): static {
    return new static(
      $container->get(EntityTypeManagerInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $entity_types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($entity_type->entityClassImplements(ConfigEntityInterface::class) && $entity_type->entityClassImplements(SectionListInterface::class)) {
        $entity_types[] = $entity_type->id();
      }
    }
    $base_plugin_definition['entity_types'] = $entity_types;
    $this->derivatives['addComponent'] = [
      'admin_label' => $this->t('Add component'),
    ] + $base_plugin_definition;
    return $this->derivatives;
  }

}
