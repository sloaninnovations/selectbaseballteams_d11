<?php

namespace Drupal\layout_builder\Form;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\InlineBlockUsageInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to confirm the removal of a block.
 */
final class MakeReusableBlockForm extends LayoutRebuildConfirmFormBase {

  /**
   * The current region.
   *
   * @var string
   */
  protected $region;

  /**
   * The UUID of the block being removed.
   *
   * @var string
   */
  protected $uuid;

  /**
   * Constructs a new MakeReusableBlockForm.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   Block manager.
   */
  public function __construct(
    LayoutTempstoreRepositoryInterface $layout_tempstore_repository,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected BlockManagerInterface $blockManager,
    protected InlineBlockUsageInterface $inlineBlockUsage
  ) {
    parent::__construct($layout_tempstore_repository);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.block'),
      $container->get('inline_block.usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $label = $this->sectionStorage
      ->getSection($this->delta)
      ->getComponent($this->uuid)
      ->getPlugin()
      ->label();

    return $this->t('Are you sure you want to make the %label block reusable?', ['%label' => $label]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t(
      'This action cannot be undone. If you will make block reusable it will be available in blocks selection.
    The block will appear in the custom block library and it can be used in more than one place.'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Make reusable');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_make_reusable_block';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    SectionStorageInterface $section_storage = NULL,
    $delta = NULL,
    $region = NULL,
    $uuid = NULL
  ) {
    $this->region = $region;
    $this->uuid = $uuid;

    return parent::buildForm($form, $form_state, $section_storage, $delta);
  }

  /**
   * {@inheritdoc}
   */
  protected function handleSectionStorage(SectionStorageInterface $section_storage, FormStateInterface $form_state) {
    $section = $section_storage->getSection($this->delta);
    $component = $section->getComponent($this->uuid);
    $component_configuration = $component->get('configuration');

    $block_content = NULL;
    if (!empty($component_configuration['block_serialized'])) {
      $block_content = unserialize($component_configuration['block_serialized']);
    }
    elseif (!empty($component_configuration['block_revision_id'])) {
      $block_content = $this->entityTypeManager->getStorage('block_content')->loadRevision($component_configuration['block_revision_id']);
    }

    // Not sure how to handle it properly.
    if (!($block_content instanceof BlockContent)) {
      return;
    }

    $block_content->setReusable();
    $block_content->save();

    // Delete existing usage for the block since it is no longer an inline
    // block.
    $this->inlineBlockUsage->deleteUsage([$block_content->id()]);

    assert($block_content instanceof BlockContent);
    $converter_block = $this->blockManager->createInstance('block_content:' . $block_content->uuid(), [
      'view_mode' => $component_configuration['view_mode'],
      'label' => $component_configuration['label'],
      'type' => $block_content->bundle(),
      'uuid' => $block_content->uuid(),
      'label_display' => $component_configuration['label_display'],
    ]);

    $section->getComponent($this->uuid)->setConfiguration($converter_block->getConfiguration());
  }

}
