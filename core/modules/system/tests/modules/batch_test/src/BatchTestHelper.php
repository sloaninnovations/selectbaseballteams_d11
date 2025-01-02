<?php

declare(strict_types=1);

namespace Drupal\batch_test;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormState;

class BatchTestHelper {

  /**
   * Batch operation: Submits form_test_mock_form().
   */
  public function nestedDrupalFormSubmitCallback($value): void {
    $form_state = (new FormState())
      ->setValue('test_value', $value);
    \Drupal::formBuilder()->submitForm('Drupal\batch_test\Form\BatchTestMockForm', $form_state);
  }

  /**
   * Batch 0: Does nothing.
   */
  public function batch_0(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    $batch_builder = (new BatchBuilder())
      ->setFinishCallback([$batch_test_callbacks, 'finished_0']);
    return $batch_builder->toArray() + ['batch_test_id' => 'batch_0'];
  }

  /**
   * Batch 1: Repeats a simple operation.
   *
   * Operations: op 1 from 1 to 10.
   */
  public function batch_1(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    // Ensure the batch takes at least two iterations.
    $total = 10;
    $sleep = (int) (1000000 / $total) * 2;

    $batch_builder = (new BatchBuilder())
      ->setFinishCallback([$batch_test_callbacks, 'finished_1']);

    for ($i = 1; $i <= $total; $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback_1'], [$i, $sleep]);
    }

    return $batch_builder->toArray() + ['batch_test_id' => 'batch_1'];
  }

  /**
   * Batch 2: Performs a single multistep operation.
   *
   * Operations: op 2 from 1 to 10.
   */
  public function batch_2(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    // Ensure the batch takes at least two iterations.
    $total = 10;
    $sleep = (1000000 / $total) * 2;

    $batch_builder = (new BatchBuilder())
      ->addOperation([$batch_test_callbacks, 'callback_2'], [1, $total, $sleep])
      ->setFinishCallback([$batch_test_callbacks, 'finished_2']);

    return $batch_builder->toArray() + ['batch_test_id' => 'batch_2'];
  }

  /**
   * Batch 3: Performs both single and multistep operations.
   *
   * Operations:
   * - op 1 from 1 to 5,
   * - op 2 from 1 to 5,
   * - op 1 from 6 to 10,
   * - op 2 from 6 to 10.
   */
  public function batch_3(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    // Ensure the batch takes at least two iterations.
    $total = 10;
    $sleep = (1000000 / $total) * 2;

    $batch_builder = (new BatchBuilder())
      ->setFinishCallback([$batch_test_callbacks, 'finished_3']);
    for ($i = 1; $i <= round($total / 2); $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback_1'], [$i, $sleep]);
    }
    $batch_builder->addOperation([$batch_test_callbacks, 'callback_2'], [1, $total / 2, $sleep]);
    for ($i = round($total / 2) + 1; $i <= $total; $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback_1'], [$i, $sleep]);
    }
    $batch_builder->addOperation([$batch_test_callbacks, 'callback_2'], [6, $total / 2, $sleep]);

    return $batch_builder->toArray() + ['batch_test_id' => 'batch_3'];
  }

  /**
   * Batch 4: Performs a batch within a batch.
   *
   * Operations:
   * - op 1 from 1 to 5,
   * - set batch 2 (op 2 from 1 to 10, should run at the end)
   * - op 1 from 6 to 10,
   */
  public function batch_4(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    // Ensure the batch takes at least two iterations.
    $total = 10;
    $sleep = (1000000 / $total) * 2;

    $batch_builder = (new BatchBuilder())
      ->setFinishCallback([$batch_test_callbacks, 'finished_4']);
    for ($i = 1; $i <= round($total / 2); $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback_1'], [$i, $sleep]);
    }
    $batch_builder->addOperation([$batch_test_callbacks, 'nestedBatchCallback'], [[2]]);
    for ($i = round($total / 2) + 1; $i <= $total; $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback_1'], [$i, $sleep]);
    }

    return $batch_builder->toArray() + ['batch_test_id' => 'batch_4'];
  }

  /**
   * Batch 5: Repeats a simple operation.
   *
   * Operations: op 1 from 1 to 10.
   */
  public function batch_5(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    // Ensure the batch takes at least two iterations.
    $total = 10;
    $sleep = (1000000 / $total) * 2;

    $batch_builder = (new BatchBuilder())
      ->setFinishCallback([$batch_test_callbacks, 'finished_5']);
    for ($i = 1; $i <= $total; $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback_5'], [$i, $sleep]);
    }

    return $batch_builder->toArray() + ['batch_test_id' => 'batch_5'];
  }

  /**
   * Batch 6: Repeats a simple operation.
   *
   * Operations: op 6 from 1 to 10.
   */
  public function batch_6(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    // Ensure the batch takes at least two iterations.
    $total = 10;
    $sleep = (1000000 / $total) * 2;

    $batch_builder = (new BatchBuilder())
      ->setFinishCallback([$batch_test_callbacks, 'finished_6']);
    for ($i = 1; $i <= $total; $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback_6'], [$i, $sleep]);
    }

    return $batch_builder->toArray() + ['batch_test_id' => 'batch_6'];
  }

  /**
   * Batch 7: Performs two batches within a batch.
   *
   * Operations:
   * - op 7 from 1 to 5,
   * - set batch 5 (op 5 from 1 to 10, should run at the end before batch 2)
   * - set batch 6 (op 6 from 1 to 10, should run at the end after batch 1)
   * - op 7 from 6 to 10,
   */
  public function batch_7(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    // Ensure the batch takes at least two iterations.
    $total = 10;
    $sleep = (1000000 / $total) * 2;

    $batch_builder = (new BatchBuilder())
      ->setFinishCallback([$batch_test_callbacks, 'finished_7']);
    for ($i = 1; $i <= $total / 2; $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback_7'], [$i, $sleep]);
    }
    $batch_builder->addOperation([$batch_test_callbacks, 'nestedBatchCallback'], [[6, 5]]);
    for ($i = ($total / 2) + 1; $i <= $total; $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback_7'], [$i, $sleep]);
    }

    return $batch_builder->toArray() + ['batch_test_id' => 'batch_7'];
  }

  /**
   * Batch 8: Throws an exception.
   */
  public function batch_8(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    $batch_builder = (new BatchBuilder())
      ->addOperation([$batch_test_callbacks, 'callback_8'], [FALSE])
      ->addOperation([$batch_test_callbacks, 'callback_8'], [TRUE]);
    return $batch_builder->toArray() + ['batch_test_id' => 'batch_8'];
  }

  /**
   * Implements callback_batch_operation().
   *
   * Tests the progress page theme.
   */
  public function themeCallback(): void {
    // Because drupalGet() steps through the full progressive batch before
    // returning control to the test function, we cannot test that the correct
    // theme is being used on the batch processing page by viewing that page
    // directly. Instead, we save the theme being used in a variable here, so
    // that it can be loaded and inspected in the thread running the test.
    $theme = \Drupal::theme()->getActiveTheme()->getName();
    $this->stack($theme);
  }

  /**
   * Tests the title on the progress page by performing a batch callback.
   */
  public function titleCallback(): void {
    // Because drupalGet() steps through the full progressive batch before
    // returning control to the test function, we cannot test that the correct
    // title is being used on the batch processing page by viewing that page
    // directly. Instead, we save the title being used in a variable here, so
    // that it can be loaded and inspected in the thread running the test.
    $request = \Drupal::request();
    $route_match = \Drupal::routeMatch();
    $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
    $this->stack($title);
  }

  /**
   * Helper function: Stores or retrieves traced execution data.
   */
  public function stack($data = NULL, $reset = FALSE): array|null {
    $state = \Drupal::state();
    if ($reset) {
      $state->delete('batch_test.stack');
    }
    if (!isset($data)) {
      return $state->get('batch_test.stack');
    }
    $stack = $state->get('batch_test.stack');
    $stack[] = $data;
    $state->set('batch_test.stack', $stack);

    return NULL;
  }

}
