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
    ];
  }

  /**
   * Tests update path for the contact form's values from '' to NULL.
   */
  public function testRunUpdates() {
    $this->expectDeprecation("Setting empty 'reply' is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. See https://www.drupal.org/project/drupal/issues/3445976");
    $this->expectDeprecation("Setting empty 'redirect' is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. See https://www.drupal.org/project/drupal/issues/3445976");
    $this->assertSame('', ContactForm::load('feedback')->get('redirect'));
    $this->assertSame('', ContactForm::load('feedback')->get('reply'));

    $this->runUpdates();

    $this->assertNull(ContactForm::load('feedback')->get('redirect'));
    $this->assertNull(ContactForm::load('feedback')->get('reply'));
  }

}
