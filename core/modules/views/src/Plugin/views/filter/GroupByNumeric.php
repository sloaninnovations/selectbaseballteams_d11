<?php

namespace Drupal\views\Plugin\views\filter;

use Drupal\views\Attribute\ViewsFilter;

/**
 * Simple filter to handle greater than/less than filters.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("groupby_numeric")]
class GroupByNumeric extends NumericFilter {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $field = $this->getField();

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

  /**
   * Applies a 'between' or 'not between' filter to the query.
   *
   * @param string $field
   *   The field to apply the filter to.
   */
  protected function opBetween($field) {
    $placeholder_min = $this->placeholder();
    $placeholder_max = $this->placeholder();
    if ($this->operator == 'between') {
      $this->query->addHavingExpression($this->options['group'], "$field >= $placeholder_min", [$placeholder_min => $this->value['min']]);
      $this->query->addHavingExpression($this->options['group'], "$field <= $placeholder_max", [$placeholder_max => $this->value['max']]);
    }
    else {
      $this->query->addHavingExpression($this->options['group'], "$field < $placeholder_min OR $field > $placeholder_max", [$placeholder_min => $this->value['min'], $placeholder_max => $this->value['max']]);
    }
  }

  /**
   * Applies a simple filter operation to the query.
   *
   * @param string $field
   *   The field to apply the filter to.
   */
  protected function opSimple($field) {
    $placeholder = $this->placeholder();
    $this->query->addHavingExpression($this->options['group'], "$field $this->operator $placeholder", [$placeholder => $this->value['value']]);
  }

  /**
   * Applies an "empty" or "not empty" filter operation to the query.
   *
   * @param string $field
   *   The field to apply the filter to.
   */
  protected function opEmpty($field) {
    if ($this->operator == 'empty') {
      $operator = "IS NULL";
    }
    else {
      $operator = "IS NOT NULL";
    }

    $this->query->addHavingExpression($this->options['group'], "$field $operator");
  }

  /**
   * {@inheritdoc}
   */
  public function adminLabel($short = FALSE) {
    return $this->getField(parent::adminLabel($short));
  }

  /**
   * {@inheritdoc}
   */
  public function canGroup() {
    return FALSE;
  }

}
