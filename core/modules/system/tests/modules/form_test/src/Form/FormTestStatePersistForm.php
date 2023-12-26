<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Form constructor for testing form state persistence.
 *
 * @internal
 */
class FormTestStatePersistForm extends FormBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_state_persist';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => 'title',
      '#default_value' => 'DEFAULT',
      '#required' => TRUE,
    ];
    $form_state->set('value', 'State persisted.');

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['#process'][] = [$this, 'setStateRebuildValue'];
    $form['#post_render'][] = [static::class, 'displayCachedState'];
    return $form;
  }

  /**
   * Form API #process callback.
   *
   * Set form state properties based on whether form is rebuilding.
   */
  public function setStateRebuildValue(array $form, FormStateInterface $form_state): array {
    if (!$form_state->isRebuilding()) {
      $form_state->set('process_value', TRUE);
    }
    else {
      $form_state->set('rebuild_value', TRUE);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($form_state->get('value'))
      ->addStatus($form_state->get('process_value') ? 'Process state persisted.' : 'Process state not persisted.')
      ->addStatus($form_state->get('rebuild_value') ? 'Rebuild state persisted.' : 'Rebuild state not persisted.');
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['displayCachedState'];
  }

  /**
   * Render API #post_render callback.
   *
   * After form is rendered, add status messages displaying form state
   * 'processed_value' and 'rebuilt_value'.
   */
  public static function displayCachedState(string $rendered_form, array $form): string {
    $form_state = new FormState();
    \Drupal::formBuilder()->getCache($form['#build_id'], $form_state);
    \Drupal::messenger()
      ->addStatus($form_state->get('process_value') ? 'Process state cached.' : 'Process state not cached.')
      ->addStatus($form_state->get('rebuild_value') ? 'Rebuild state cached.' : 'Rebuild state not cached.');
    return $rendered_form;
  }

}
