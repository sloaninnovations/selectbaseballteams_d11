<?php

namespace Drupal\system\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Clear caches for this site.
 *
 * @internal
 */
class ClearCacheForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_clear_cache';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['clear_cache'] = [
      '#type' => 'details',
      '#title' => $this->t('Clear cache'),
      '#open' => TRUE,
    ];

    $form['clear_cache']['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear all caches'),
    ];

    return $form;
  }

  /**
   * Clears the caches.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Cache\CacheClearerInterface $cacheClearer */
    $cacheClearer = \Drupal::service('cache.chain_cache_clearer');
    $cacheClearer->clearCache();
    $this->messenger()->addStatus($this->t('Caches cleared.'));
  }

}
