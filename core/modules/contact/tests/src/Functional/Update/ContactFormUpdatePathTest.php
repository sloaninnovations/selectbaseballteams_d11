<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Functional\Update;

use Drupal\contact\Entity\ContactForm;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update path for the contact form values from '' to NULL.
 *
 * @group contact
 * @group legacy
 */
class ContactFormUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/remove-message-from-contact-feedback-form.php',
    ];
  }

  /**
   * Tests update path for the contact form's values from '' to NULL.
   */
  public function testRunUpdates() {
    $this->expectDeprecation('Setting reply to an empty string is deprecated in drupal:10.4.0 and it must be null in drupal:12.0.0. See https://www.drupal.org/node/3445976');
    $this->expectDeprecation('Setting message to an empty string is deprecated in drupal:10.4.0 and it must be null in drupal:12.0.0. See https://www.drupal.org/node/3445976');
    $this->expectDeprecation('Setting redirect to an empty string is deprecated in drupal:10.4.0 and it must be null in drupal:12.0.0. See https://www.drupal.org/node/3445976');
    $this->assertSame('', ContactForm::load('feedback')->get('message'));
    $this->assertSame('', ContactForm::load('feedback')->get('redirect'));
    $this->assertSame('', ContactForm::load('feedback')->get('reply'));

    $this->runUpdates();

    $this->assertNull(ContactForm::load('feedback')->get('message'));
    $this->assertNull(ContactForm::load('feedback')->get('redirect'));
    $this->assertNull(ContactForm::load('feedback')->get('reply'));
  }

}
