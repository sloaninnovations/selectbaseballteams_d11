<?php

namespace Drupal\layout_builder\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\LayoutBuilderHighlightTrait;
use Drupal\layout_builder\Plugin\Block\InlineBlock;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides a form to add a block.
 *
 * @internal
 *   Form classes are internal.
 */
class AddBlockForm extends ConfigureBlockFormBase {

  use LayoutBuilderHighlightTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_add_block';
  }

  /**
   * {@inheritdoc}
   */
  protected function submitLabel() {
    return $this->t('Add block');
  }

  /**
   * Builds the form for the block.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage being configured.
   * @param int $delta
   *   The delta of the section.
   * @param string $region
   *   The region of the block.
   * @param string|null $plugin_id
   *   The plugin ID of the block to add.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?SectionStorageInterface $section_storage = NULL, $delta = NULL, $region = NULL, $plugin_id = NULL) {
    // Only generate a new component once per form submission.
    if (!$component = $form_state->get('layout_builder__component')) {
      $component = new SectionComponent($this->uuidGenerator->generate(), $region, ['id' => $plugin_id]);
      $section_storage->getSection($delta)->appendComponent($component);
      $form_state->set('layout_builder__component', $component);

      if ($component->getPlugin() instanceof InlineBlock && !$form_state->get('second_step')) {
        return $this->buildFirstStep($form, $form_state);
      }

      // In case if that's not inline block context set indicator that this is
      // one step form.
      $form_state->set('one_step_form', TRUE);
    }

    $form['#attributes']['data-layout-builder-target-highlight-id'] = $this->blockAddHighlightId($delta, $region);
    return $this->doBuildForm($form, $form_state, $section_storage, $delta, $component);
  }

  /**
   * Builds first step where user can choose block type.
   */
  protected function buildFirstStep(array $form, FormStateInterface $form_state): array {
    $form['#id'] = Html::getId($form_state->getBuildInfo()['form_id']);

    $form['actions'] = [
      'inline_block' => [
        '#type' => 'submit',
        '#value' => t('Create inline block'),
        '#submit' => [[static::class, 'firstStep']],
        '#ajax' => [
          'callback' => '::ajaxSubmit',
          'wrapper' => $form['#id'],
        ],
        '#action' => 'inline',
      ],
      'reusable_block' => [
        '#type' => 'submit',
        '#value' => t('Create reusable block'),
        '#submit' => [[static::class, 'firstStep']],
        '#ajax' => [
          'callback' => '::ajaxSubmit',
          'wrapper' => $form['#id'],
        ],
        '#action' => 'reusable',
      ],
    ];

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Inline block is a block that cannot be reused. Reusable block instead can be reused but there are few caveats:'),
      'caveats' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Reusable block cannot be converted to inline'),
          $this->t('If you will make block reusable it will be available in blocks selection'),
          $this->t('The block will appear in the custom block library and it can be used in more than one place'),
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('second_step') || $form_state->get('one_step_form')) {
      parent::validateForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Make sure that appropriate ajax handler will be triggered.
    // @see self::successfulAjaxSubmit
    $form_state->set('is_last_step', TRUE);
    parent::submitForm($form, $form_state);
  }

  /**
   * Selects what block type will be created.
   */
  public static function firstStep($form, FormStateInterface $form_state) {
    $form_state->set('second_step', TRUE);
    $form_state->set('make_reusable', $form_state->getTriggeringElement()['#action'] === 'reusable');
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    if ($form_state->get('is_last_step')) {
      return parent::successfulAjaxSubmit($form, $form_state);
    }

    return $form;
  }

}
