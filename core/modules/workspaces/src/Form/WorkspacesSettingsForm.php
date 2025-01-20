<?php

declare(strict_types=1);

namespace Drupal\workspaces\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Workspaces.
 */
final class WorkspacesSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['workspaces.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'workspaces_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('workspaces.settings');

    $form['allow_parallel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow parallel Workspaces'),
      '#description' => $this->t('Users will be able to edit content across multiple workspaces.'),
      '#config_target' => 'workspaces.settings:allow_parallel',
    ];

    return parent::buildForm($form, $form_state);
  }

}
