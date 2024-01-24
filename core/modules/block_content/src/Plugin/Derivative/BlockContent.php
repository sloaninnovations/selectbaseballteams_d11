<?php

namespace Drupal\block_content\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieves block plugin definitions for all content blocks.
 */
class BlockContent extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The content block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentStorage;

  /**
   * Constructs a BlockContent object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_storage
   *   The content block storage.
   */
  public function __construct(EntityStorageInterface $block_content_storage) {
    $this->blockContentStorage = $block_content_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('block_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $block_contents = $this->blockContentStorage->getAggregateQuery()
      ->condition('reusable', TRUE)
      ->groupBy('uuid')
      ->groupBy('info')
      ->accessCheck(FALSE)
      ->groupBy('type')
      ->execute();
    // Reset the discovered definitions.
    $this->derivatives = [];
    foreach ($block_contents as $block_content) {
      $uuid = $block_content['uuid'];
      $this->derivatives[$uuid] = $base_plugin_definition;
      $this->derivatives[$uuid]['admin_label'] = $block_content['label'];
      $this->derivatives[$uuid]['config_dependencies']['content'] = [
        sprintf('block_content:%s:%s', $block_content['type'], $block_content['uuid']),
      ];
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
