<?php

declare(strict_types=1);

namespace Drupal\workflows\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\StateInterface;
use Drupal\workflows\TransitionInterface;
use Drupal\workflows\WorkflowStateItemInterface;

/**
 * Plugin implementation of the 'workflow_state' field type.
 *
 * @FieldType(
 *   id = "workflow_state",
 *   label = @Translation("Workflow state"),
 *   description = @Translation("Stores the current workflow state."),
 *   default_widget = "options_select",
 *   default_formatter = "list_default",
 *   list_class = "\Drupal\workflows\WorkflowStateFieldItemList",
 *   cardinality = 1,
 * )
 */
class WorkflowStateItem extends FieldItemBase implements WorkflowStateItemInterface, OptionsProviderInterface {

  /**
   * The original value, used to validate state changes.
   *
   * @var string
   */
  protected $originalValue;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('State'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar_ascii',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'workflow' => '',
      'workflow_callback' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];
    // Allow the workflow to be changed if it's not determined by a callback.
    if (!$this->getSetting('workflow_callback')) {
      $options = [];
      foreach (Workflow::loadMultiple() as $workflow) {
        $options[$workflow->id()] = $workflow->label();
      }

      $element['workflow'] = [
        '#type' => 'select',
        '#title' => $this->t('Workflow'),
        '#options' => $options,
        '#default_value' => $this->getSetting('workflow'),
        '#disabled' => $has_data,
        '#required' => TRUE,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // Note that in this field's case the value will never be empty
    // because of the default returned in applyDefaultValue().
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    if ($workflow = $this->getWorkflow()) {
      $initial_state = $workflow->getTypePlugin()->getInitialState();
      $this->setValue(['value' => $initial_state->id()], $notify);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (empty($this->originalValue)) {
      // If no array is given, then the method received just the state value.
      if (isset($values) && !is_array($values)) {
        $values = ['value' => $values];
      }
      // Track the original field value to allow isValid() to validate changes
      // and to react to transitions.
      $this->originalValue = $values['value'];
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    $field_name = $this->getFieldDefinition()->getName();
    $value = $this->getEntity()->get($field_name)->value;
    $allowed_states = $this->getAllowedStates($this->originalValue);
    return isset($allowed_states[$value]);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(?AccountInterface $account = NULL) {
    return array_keys($this->getPossibleOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(?AccountInterface $account = NULL) {
    $workflow = $this->getWorkflow();
    $state_labels = array_map(function (StateInterface $state) {
      return $state->label();
    }, $workflow->getTypePlugin()->getStates());

    return $state_labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(?AccountInterface $account = NULL) {
    return array_keys($this->getSettableOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(?AccountInterface $account = NULL) {
    // $this->value is unpopulated due to https://www.drupal.org/node/2629932
    $field_name = $this->getFieldDefinition()->getName();
    $value = $this->getEntity()->get($field_name)->value;
    $allowed_states = $this->getAllowedStates($value);
    $state_labels = array_map(function (StateInterface $state) {
      return $state->label();
    }, $allowed_states);

    return $state_labels;
  }

  /**
   * Gets the next allowed states for the given field value.
   *
   * @param string $value
   *   The field value, representing the state ID.
   *
   * @return \Drupal\workflows\StateInterface[]
   *   The allowed states.
   */
  protected function getAllowedStates($value) {
    $allowed_states = [];
    $workflow = $this->getWorkflow();
    foreach ($workflow->getTypePlugin()->getState($value)->getTransitions() as $transition) {
      $state = $transition->to();
      $allowed_states[$state->id()] = $state;
    }

    return $allowed_states;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflow() {
    if ($callback = $this->getSetting('workflow_callback')) {
      $workflow_id = call_user_func($callback, $this->getFieldDefinition());
    }
    else {
      $workflow_id = $this->getSetting('workflow');
    }

    return Workflow::load($workflow_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalStateId() {
    return $this->originalValue;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateId() {
    $field_name = $this->getFieldDefinition()->getName();
    $value = $this->getEntity()->get($field_name)->value;
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateLabel() {
    $field_name = $this->getFieldDefinition()->getName();
    $value = $this->getEntity()->get($field_name)->value;
    $state = $this->getWorkflow()->getTypePlugin()->getState($value);
    return $state->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitions() {
    $field_name = $this->getFieldDefinition()->getName();
    $value = $this->getEntity()->get($field_name)->value;
    $state = $this->getWorkflow()->getTypePlugin()->getState($value);
    return $state->getTransitions();
  }

  /**
   * {@inheritdoc}
   */
  public function applyTransition(TransitionInterface $transition) {
    $this->setValue(['value' => $transition->to()->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function applyTransitionById($transition_id) {
    $transition = $this->getWorkflow()->getTypePlugin()->getTransition($transition_id);
    $this->applyTransition($transition);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $field_name = $this->getFieldDefinition()->getName();
    $value = $this->getEntity()->get($field_name)->value;
    if ($value != $this->originalValue) {
      $this->invokeTransitionHook('pre_transition');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $field_name = $this->getFieldDefinition()->getName();
    $value = $this->getEntity()->get($field_name)->value;
    if ($value != $this->originalValue) {
      $this->invokeTransitionHook('post_transition');
    }
    $this->originalValue = $value;
    return parent::postSave($update);
  }

  /**
   * Invokes a transition hook for the given phase.
   *
   * @param string $phase
   *   The phase: pre_transition OR post_transition.
   */
  protected function invokeTransitionHook($phase) {
    $workflow = $this->getWorkflow();
    $field_name = $this->getFieldDefinition()->getName();
    $value = $this->getEntity()->get($field_name)->value;
    if ($workflow->getTypePlugin()->hasTransitionFromStateToState($this->originalValue, $value)) {
      $transition = $workflow->getTypePlugin()->getTransitionFromStateToState($this->originalValue, $value);

      // Invoke the hook.
      $entity = $this->getEntity();
      $field_name = $this->getFieldDefinition()->getName();
      \Drupal::moduleHandler()->invokeAll('workflows_' . $phase, [$workflow, $transition, $entity, $field_name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateDependencies(FieldDefinitionInterface $field_definition) {
    $dependencies = [];

    if ($callback = $field_definition->getSetting('workflow_callback')) {
      $workflow_id = call_user_func($callback, $field_definition);
    }
    else {
      $workflow_id = $field_definition->getSetting('workflow');
    }
    if ($workflow_id && ($workflow = Workflow::load($workflow_id))) {
      $dependencies['config'][] = $workflow->getConfigDependencyName();
    }

    return $dependencies;
  }

}
