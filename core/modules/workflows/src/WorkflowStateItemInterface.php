<?php

namespace Drupal\workflows;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines the interface for state field items.
 */
interface WorkflowStateItemInterface extends FieldItemInterface {

  /**
   * Gets the workflow used by the field.
   *
   * @return \Drupal\workflows\WorkflowInterface
   *   The workflow entity.
   */
  public function getWorkflow();

  /**
   * Gets the original state ID.
   *
   * If the state ID has been changed after the entity was constructed/loaded,
   * the original ID will hold the previous value.
   *
   * Use this as an alternative to getting the state ID from $entity->original.
   *
   * @return string
   *   The original state ID.
   */
  public function getOriginalStateId();

  /**
   * Gets the current state ID.
   *
   * @return string
   *   The current state ID.
   */
  public function getStateId();

  /**
   * Gets the label of the current state.
   *
   * @return string
   *   The label of the current state.
   */
  public function getStateLabel();

  /**
   * Gets the allowed transitions for the current state.
   *
   * @return \Drupal\workflows\TransitionInterface[]
   *   The allowed transitions, keyed by transition ID.
   */
  public function getTransitions();

  /**
   * Applies the given transition, changing the current state.
   *
   * @param \Drupal\workflows\TransitionInterface $transition
   *   The transition to apply.
   */
  public function applyTransition(TransitionInterface $transition);

  /**
   * Applies a transition with the given ID, changing the current state.
   *
   * @param string $transition_id
   *   The transition ID.
   */
  public function applyTransitionById($transition_id);

  /**
   * Determines whether the current state is valid.
   *
   * @return bool
   *   TRUE if the current state is valid, FALSE otherwise.
   */
  public function isValid();

}
