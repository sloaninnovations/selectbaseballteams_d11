<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\FilterPluginBaseTest.
 */

namespace Drupal\views_ui\Tests;

/**
 * Tests the FilterPluginBase class.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\filter\FilterPluginBase
 */
class FilterPluginBaseTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_filter_plugin_base');

  /**
   * Tests whether the FilterPluginTest::groupForm() function works as expected.
   */
  public function testGroupForm() {
    $edit = [
      'name[views_test_data.created]' => TRUE,
      'name[views_test_data.status]' => TRUE
    ];
    $this->drupalPostForm('admin/structure/views/nojs/add-handler/test_filter_plugin_base/default/filter', $edit, t('Add and configure @handler', array('@handler' => t('filter criteria'))));

    $edit = [
      'options[expose_button][checkbox][checkbox]' => TRUE,
      'options[operator]' => '>',
      'options[value][value]' => '1990-01-01 00:00:00'
    ];
    $this->drupalPostForm(NULL, $edit, t('Expose filter'));

    $edit = ['options[group_button][radios][radios]' => TRUE];
    $this->drupalPostForm(NULL, $edit, t('Grouped filters'));

    $edit = ['options[group_info][multiple]' => TRUE];
    $this->drupalPostForm(NULL, $edit, t('Apply and continue'));

    $edit = ['options[value]' => TRUE];
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_filter_plugin_base/default/filter/status', $edit, t('Apply'));
    $this->drupalPostForm('admin/structure/views/view/test_filter_plugin_base/edit/default', [], t('Update preview'));
    $this->assertRaw('<span class="field-content">1</span>');
    $this->assertRaw('<span class="field-content">3</span>');
    $this->assertRaw('<span class="field-content">5</span>');
  }

}
