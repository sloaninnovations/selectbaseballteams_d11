<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the form state persists across multiple requests.
 *
 * @group Form
 */
class FormStatePersistTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test that form state persists correctly after being submitted and rebuilt.
   */
  public function testFormStatePersistence(): void {
    $this->drupalGet('form-test/state-persist');

    $assert_session = $this->assertSession();
    // The form has a #post_render callback that displays whether form state
    // properties set during the #process callback are cached. On the first
    // request to the form, caching is disabled because it is a GET request.
    $assert_session->statusMessageContains('Process state not cached.');
    $assert_session->statusMessageContains('Rebuild state not cached.');

    $edit = ['title' => 'DEFAULT'];
    $this->submitForm($edit, 'Submit');
    // In the form submit handler, the form state 'value' property set in
    // buildForm should have persisted. The 'process_state' property set in the
    // #process callback should have persisted. The 'rebuild_state' property set
    // in the #process hook after form rebuild will not show as persisted
    // because that value gets set after the submit handler has run.
    $assert_session->statusMessageContains('State persisted.');
    $assert_session->statusMessageContains('Process state persisted.');
    $assert_session->statusMessageContains('Rebuild state not persisted.');

    // Status messages added by the #post_render callback should now show that
    // the process_state and rebuild_state form state properties are now cached.
    $assert_session->statusMessageContains('Process state cached.');
    $assert_session->statusMessageContains('Rebuild state cached.');

    // Submit the form again to show continued persistence.
    $edit = ['title' => 'DEFAULT'];
    $this->submitForm($edit, 'Submit');

    // After submitting the form a second time, the 'rebuild_state' property set
    // during the rebuild after first submission should persist and be displayed
    // correctly in the submit handler.
    $assert_session->statusMessageContains('State persisted.');
    $assert_session->statusMessageContains('Process state persisted');
    $assert_session->statusMessageContains('Rebuild state persisted.');

    $assert_session->statusMessageContains('Process state cached.');
    $assert_session->statusMessageContains('Rebuild state cached.');
  }

}
