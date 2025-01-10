<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\views_ui\Traits\FilterEntityReferenceTrait;

/**
 * Tests the entity reference filter UI.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\filter\EntityReference
 */
class FilterEntityReferenceWebTest extends UITestBase {

  use FilterEntityReferenceTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_filter_entity_reference'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views);
    $this->setUpEntityTypes();
  }

  /**
   * Tests the filter UI.
   */
  public function testFilterUi(): void {
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_entity_reference/default/filter/field_test_target_id');

    $options = $this->getUiOptions();
    // Should be sorted by title ASC.
    uasort($this->targetEntities, function (EntityInterface $a, EntityInterface $b) {
      return strnatcasecmp($a->getTitle(), $b->getTitle());
    });
    $i = 0;
    foreach ($this->targetEntities as $id => $entity) {
      $this->assertEquals($options[$i]['label'], $entity->label(), new FormattableMarkup('Expected target entity label found for option :option', [':option' => $i]));
      $i++;
    }

    // Change the sort field and direction.
    $this->drupalGet('admin/structure/views/nojs/handler-extra/test_filter_entity_reference/default/filter/field_test_target_id');
    $edit = [
      'options[reference_default:node][sort][field]' => 'nid',
      'options[reference_default:node][sort][direction]' => 'DESC',
    ];
    $this->submitForm($edit, 'Apply');

    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_entity_reference/default/filter/field_test_target_id');
    // Items should now be in reverse id order.
    krsort($this->targetEntities);
    $options = $this->getUiOptions();
    $i = 0;
    foreach ($this->targetEntities as $id => $entity) {
      $this->assertEquals($options[$i]['label'], $entity->label(), new FormattableMarkup('Expected target entity label found for option :option', [':option' => $i]));
      $i++;
    }

    // Change bundle types.
    $this->drupalGet('admin/structure/views/nojs/handler-extra/test_filter_entity_reference/default/filter/field_test_target_id');
    $edit = [
      "options[reference_default:node][target_bundles][{$this->hostEntityType->id()}]" => TRUE,
      "options[reference_default:node][target_bundles][{$this->targetEntityType->id()}]" => TRUE,
    ];
    $this->submitForm($edit, 'Apply');

    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_entity_reference/default/filter/field_test_target_id');
    $options = $this->getUiOptions();
    $i = 0;
    foreach ($this->hostEntities + $this->targetEntities as $id => $entity) {
      $this->assertEquals($options[$i]['label'], $entity->label(), new FormattableMarkup('Expected target entity label found for option :option', [':option' => $i]));
      $i++;
    }
  }

  /**
   * Helper method to parse options from the UI.
   *
   * @return array
   *   Array of keyed arrays containing the id and label of each option.
   */
  protected function getUiOptions() {
    /** @var \Behat\Mink\Element\TraversableElement[] $result */
    $result = $this->xpath('//select[@name="options[value][]"]/option');
    $this->assertNotEmpty($result, 'Options found');

    $options = [];
    foreach ($result as $option) {
      $options[] = [
        'id' => (int) $option->getValue(),
        'label' => (string) $option->getText(),
      ];
    }

    return $options;
  }

  /**
   * Tests the filter UI for config reference.
   */
  public function testFilterConfigUi(): void {
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_entity_reference/default/filter/field_test_config_target_id_reference');

    $options = $this->getUiOptions();
    // We should the content types defined as options.
    $this->assertEquals(['article', 'page'], array_column($options, 'label'));
  }

}
