<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for changing a section's layout.
 *
 * @internal
 *   Form classes are internal.
 */
class ConfigureNewSectionLayoutForm extends SectionFormBase {

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * Constructs a new ConfigureNewSectionLayoutForm.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   The layout plugin manager.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, PluginFormFactoryInterface $plugin_form_manager, LayoutPluginManagerInterface $layout_plugin_manager) {
    parent::__construct($layout_tempstore_repository, $plugin_form_manager);
    $this->layoutPluginManager = $layout_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('plugin_form.factory'),
      $container->get('plugin.manager.core.layout')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_configure_new_section_layout';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $plugin_id = NULL) {
    $this->sectionStorage = $section_storage;
    $this->delta = $delta;

    $this->layout = $this->layoutPluginManager->createInstance($plugin_id);
    $form = parent::buildForm($form, $form_state);

    $section = $this->sectionStorage->getSection($this->delta);
    $old_layout_region_labels = $section->getLayout()->getPluginDefinition()->getRegionLabels();
    $new_layout_region_labels = $this->layout->getPluginDefinition()->getRegionLabels();
    $new_layout_first_region = array_key_first($new_layout_region_labels);
    $new_region_mapping = [];
    foreach ($old_layout_region_labels as $region => $region_label) {
      $new_region_mapping[$region] = isset($new_layout_region_labels[$region]) ? $region : $new_layout_first_region;
    }
    $form_state->set('new_region_mapping', $new_region_mapping);

    $form['actions']['submit']['#value'] = $this->t('Update');

    $target_highlight_id = $this->sectionUpdateHighlightId($delta);
    $form['#attributes']['data-layout-builder-target-highlight-id'] = $target_highlight_id;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Call the plugin submit handler.
    $subform_state = SubformState::createForSubform($form['layout_settings'], $form, $form_state);
    $this->getPluginForm($this->layout)->submitConfigurationForm($form['layout_settings'], $subform_state);

    $old_section = $this->sectionStorage->getSection($this->delta);
    $third_party_settings = [];
    foreach ($old_section->getThirdPartyProviders() as $provider) {
      $third_party_settings[$provider] = $old_section->getThirdPartySettings($provider);
    }

    $plugin_id = $this->layout->getPluginId();
    $configuration = $this->layout->getConfiguration();
    $new_section = new Section($plugin_id, $configuration, $old_section->getComponents(), $third_party_settings);

    $region_mapping = $form_state->get('new_region_mapping');
    foreach ($new_section->getComponents() as $component) {
      $component->setRegion($region_mapping[$component->getRegion()]);
    }

    $this->sectionStorage->removeSection($this->delta);
    $this->sectionStorage->insertSection($this->delta, $new_section);

    $this->layoutTempstoreRepository->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }

}
