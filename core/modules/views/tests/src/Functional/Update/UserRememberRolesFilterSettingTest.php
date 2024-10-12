<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the role expose filter remember role settings.
 *
 * @group views
 */
class UserRememberRolesFilterSettingTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
    ];
  }

  /**
   * Test that filter values are updated properly.
   *
   * @see views_post_update_update_remember_role_empty()
   */
  public function testViewsPostUpdateBooleanFilterAcceptEmpty() {
    $view = View::load('files');
    $display = $view->get('display');
    $expected = [
      'authenticated' => 'authenticated',
      'anonymous' => '0',
      'administrator' => '0',
    ];
    $this->assertEquals($expected, $display['default']['display_options']['filters']['filename']['expose']['remember_roles']);

    $this->runUpdates();

    $view = View::load('files');
    $display = $view->get('display');
    $expected = [
      'authenticated' => 'authenticated',
    ];
    $this->assertEquals($expected, $display['default']['display_options']['filters']['filename']['expose']['remember_roles']);
  }

}
