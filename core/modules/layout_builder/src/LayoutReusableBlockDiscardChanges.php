<?php

namespace Drupal\layout_builder;

use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\Core\Entity\EntityRepository;

/**
 * Provides a mechanism for loading layouts from tempstore.
 */
class LayoutReusableBlockDiscardChanges {

  /**
   * The shared tempstore factory.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The shared tempstore factory.
   *
   * @var \Drupal\Core\Entity\EntityRepository
   */
  protected $entityRepository;

  /**
   * LayoutTempstoreRepository constructor.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   *   The shared tempstore factory.
   * @param \Drupal\Core\Entity\EntityRepository $entity_repository
   *   The entity repository service object.
   */
  public function __construct(SharedTempStoreFactory $temp_store_factory, EntityRepository $entity_repository) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->entityRepository = $entity_repository;
  }

  /**
   * Revert reusable block on discard changes to
   * previously stored version.
   */
  public function revertReusableBlock(SectionStorageInterface $section_storage) {
    $tempStore = $this->tempStoreFactory->get('inline_reusable_block');
    $routeParams = $section_storage->getLayoutBuilderUrl()->getRouteParameters();
    if (isset($routeParams['node'])) {
      $nid = $routeParams['node'];
    }
    else {
      return;
    }
    foreach ($section_storage->getSections() as $section) {
      foreach ($section->getComponents() as $component) {
        if ($component->getPlugin()
          ->getPluginDefinition()['id'] === 'block_content') {
          $explode = explode(':', $component->getPlugin()
            ->getPluginDefinition()['config_dependencies']['content'][0]);
          $block_content = $this->entityRepository->loadEntityByUuid('block_content', $explode[2]);
          $newBlock = $tempStore->get($block_content->bundle() . '--' . $block_content->uuid() . '--' . $nid);
          if ($newBlock) {
            $newBlock->setNewRevision();
            $newBlock->save();
            $tempStore->delete($block_content->bundle() . '--' . $block_content->uuid() . '--' . $nid);
          }
        }

      }
    }
  }

  /**
   * Revert reusable block on discard changes to
   * previously stored version.
   */
  public function deleteReusableBlockTemporaryStorage(SectionStorageInterface $section_storage) {
    $tempStore = $this->tempStoreFactory->get('inline_reusable_block');
    $routeParams = $section_storage->getLayoutBuilderUrl()->getRouteParameters();
    if (isset($routeParams['node'])) {
      $nid = $routeParams['node'];
    }
    else {
      return;
    }
    foreach ($section_storage->getSections() as $section) {
      foreach ($section->getComponents() as $component) {
        if ($component->getPlugin()
          ->getPluginDefinition()['id'] === 'block_content') {
          $explode = explode(':', $component->getPlugin()
            ->getPluginDefinition()['config_dependencies']['content'][0]);
          $block_content = $this->entityRepository->loadEntityByUuid('block_content', $explode[2]);
          // Delete temp storage.
          $tempStore->delete($block_content->bundle() . '--' . $block_content->uuid() . '--' . $nid);
        }

      }
    }
  }

}
