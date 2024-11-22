<?php

namespace Drupal\comment\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\FormAlter;

/**
 * Hook implementations for comment.
 */
class CommentFormAlterHooks {

  /**
   * Implements hook_form_FORM_ID_alter() for field_ui_field_storage_add_form.
   */
  #[FormAlter('field_ui_field_storage_add')]
  public function formFieldUiFieldStorageAddFormAlter(&$form, FormStateInterface $form_state) : void {
    $route_match = \Drupal::routeMatch();
    if ($form_state->get('entity_type_id') == 'comment' && $route_match->getParameter('commented_entity_type')) {
      $form['#title'] = \Drupal::service('comment.manager')->getFieldUIPageTitle($route_match->getParameter('commented_entity_type'), $route_match->getParameter('field_name'));
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[FormAlter('field_ui_form_display_overview_form')]
  public function formFieldUiFormDisplayOverviewFormAlter(&$form, FormStateInterface $form_state) : void {
    $route_match = \Drupal::routeMatch();
    if ($form['#entity_type'] == 'comment' && $route_match->getParameter('commented_entity_type')) {
      $form['#title'] = \Drupal::service('comment.manager')->getFieldUIPageTitle($route_match->getParameter('commented_entity_type'), $route_match->getParameter('field_name'));
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[FormAlter('field_ui_display_overview_form')]
  public function formFieldUiDisplayOverviewFormAlter(&$form, FormStateInterface $form_state) : void {
    $route_match = \Drupal::routeMatch();
    if ($form['#entity_type'] == 'comment' && $route_match->getParameter('commented_entity_type')) {
      $form['#title'] = \Drupal::service('comment.manager')->getFieldUIPageTitle($route_match->getParameter('commented_entity_type'), $route_match->getParameter('field_name'));
    }
  }

}
