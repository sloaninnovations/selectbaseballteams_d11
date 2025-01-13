<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\WorkspaceDynamicSafeFormInterface;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides a form for configuring a layout section.
 *
 * @internal
 *   Form classes are internal.
 */
class ConfigureSectionForm extends SectionFormBase implements WorkspaceDynamicSafeFormInterface {

  use LayoutBuilderContextTrait;
  use WorkspaceSafeFormTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?SectionStorageInterface $section_storage = NULL, $delta = NULL, $plugin_id = NULL) {
    $this->sectionStorage = $section_storage;
    $this->delta = $delta;
    $this->isUpdate = is_null($plugin_id);
    $this->pluginId = $plugin_id;

    $section = $this->getCurrentSection();
    // Passing available contexts to the layout plugin here could result in an
    // exception since the layout may not have a context mapping for a required
    // context slot on creation.
    $this->layout = $section->getLayout();
    $form_state->setTemporaryValue('gathered_contexts', $this->getPopulatedContexts($this->sectionStorage));
    $form = parent::buildForm($form, $form_state);

    if ($this->isUpdate) {
      if ($label = $section->getLayoutSettings()['label']) {
        $form['#title'] = $this->t('Configure @section', ['@section' => $label]);
      }
    }

    $target_highlight_id = $this->isUpdate ? $this->sectionUpdateHighlightId($delta) : $this->sectionAddHighlightId($delta);
    $form['#attributes']['data-layout-builder-target-highlight-id'] = $target_highlight_id;

    return $form;
  }

}
