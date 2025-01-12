<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\WorkspaceDynamicSafeFormInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_builder\LayoutReusableBlockDiscardChanges;

/**
 * Discards any pending changes to the layout.
 *
 * @internal
 *   Form classes are internal.
 */
class DiscardLayoutChangesForm extends ConfirmFormBase implements WorkspaceDynamicSafeFormInterface {

  use WorkspaceSafeFormTrait;

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * The shared tempstore factory.
   *
   * @var \Drupal\layout_builder\LayoutReusableBlockDiscardChanges
   */
  protected $layoutReusableBlockDiscardChanges;

  /**
   * Constructs a new DiscardLayoutChangesForm.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\layout_builder\LayoutReusableBlockDiscardChanges $layout_reusable_block_discard_changes
   *   The layout reusable block discard change service.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, MessengerInterface $messenger, LayoutReusableBlockDiscardChanges $layout_reusable_block_discard_changes) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->messenger = $messenger;
    $this->layoutReusableBlockDiscardChanges = $layout_reusable_block_discard_changes;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('messenger'),
      $container->get('layout_builder.reusable_block_discard_changes')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_discard_changes';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to discard your layout changes?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->sectionStorage->getLayoutBuilderUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?SectionStorageInterface $section_storage = NULL) {
    $this->sectionStorage = $section_storage;
    // Mark this as an administrative page for JavaScript ("Back to site" link).
    $form['#attached']['drupalSettings']['path']['currentPathIsAdmin'] = TRUE;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Revert reusable block to previously stored if discarded.
    $this->layoutReusableBlockDiscardChanges->revertReusableBlock($this->sectionStorage);

    $this->layoutTempstoreRepository->delete($this->sectionStorage);

    $this->messenger->addMessage($this->t('The changes to the layout have been discarded.'));

    $form_state->setRedirectUrl($this->sectionStorage->getRedirectUrl());
  }

}
