<?php

namespace Drupal\block_content\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
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
   * @param \Drupal\Core\Language\LanguageManagerInterface|null $languageManager
   *   Language manager.
   */
  public function __construct(
    protected EntityStorageInterface $block_content_storage,
    protected ?LanguageManagerInterface $languageManager = NULL) {
    if (!$this->languageManager) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $languageManager argument is deprecated in drupal:10.2.3 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3417692', E_USER_DEPRECATED);
      $this->languageManager = \Drupal::service('language_manager');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('block_content'),
      $container->get('language_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // We use an aggregate query here because we need access to UUID, info and
    // type. An entity query would only return the ID, and then we would need
    // to use ::loadMultiple to load every reusable block content entity. This
    // has a performance impact. Using an aggregate query allows us to fetch
    // the information we need to calculate the derivatives without the expense
    // of loading the entities.
    $block_contents = $this->blockContentStorage->getAggregateQuery()
      ->condition('reusable', TRUE)
      ->condition('langcode', $this->languageManager->getDefaultLanguage())
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
      $this->derivatives[$uuid]['admin_label'] = $block_content['info'];
      $this->derivatives[$uuid]['config_dependencies']['content'] = [
        sprintf('block_content:%s:%s', $block_content['type'], $block_content['uuid']),
      ];
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
