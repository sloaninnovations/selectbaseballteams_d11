<?php

declare(strict_types=1);

namespace Drupal\workflows;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workflows\Entity\Workflow;

/**
 * Defines an item list class for the 'workflow_state' field type.
 */
class WorkflowStateFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    // The default value of a workflow state field can only be the initial state
    // of the workflow.
    $storage_definition = $definition->getFieldStorageDefinition();
    if ($callback = $storage_definition->getSetting('workflow_callback')) {
      $workflow_id = call_user_func($callback, $entity);
    }
    else {
      $workflow_id = $storage_definition->getSetting('workflow');
    }

    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = Workflow::load($workflow_id);
    return [$workflow->getTypePlugin()->getInitialState()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {
    $workflow = $this->list[0]->getWorkflow();
    return [
      '#markup' => $workflow->getTypePlugin()->getState($this->list[0]->value)->label(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultValueWidget(FormStateInterface $form_state) {
    return NULL;
  }

}
