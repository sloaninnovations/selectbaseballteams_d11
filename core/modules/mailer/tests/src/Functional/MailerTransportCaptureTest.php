<?php

namespace Drupal\Tests\mailer\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

/**
 * Tests the collection of emails during testing.
 *
 * The test mailer transport (DSN drupal.test-mail://default), intercepts any
 * email sent during a test so it does not leave the test server.
 *
 * @group mailer
 */
class MailerTransportCaptureTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['mailer', 'mailer_capture_transport', 'mailer_transport_capture_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setupMailCaptureTransport();
  }

  /**
   * Tests collecting mail in the test runner.
   */
  public function testMailSendTestRunner() {
    // Create an email.
    $email = new Email();
    $email->from('admin@example.com');
    $email->to('foobar@example.com');
    $email->subject('State machine energy a production like service.');
    $email->text('We name know environmental along agree let. Traditional interest this clearly concern discover.');

    // Before we send the email, getMails should return an empty array.
    $captured_emails = $this->getMails();
    $this->assertCount(0, $captured_emails, 'The captured emails queue is empty.');

    $transport = $this->container->get('mailer.transports');
    assert($transport instanceof TransportInterface);
    $transport->send($email);

    // Ensure that there is one email in the captured emails array.
    $captured_emails = $this->getMails();
    $this->assertCount(1, $captured_emails, 'One email was captured.');
  }

  /**
   * Tests collecting mail sent in the child site.
   */
  public function testMailSendChild() {
    // Before we send the email, getMails should return an empty array.
    $captured_emails = $this->getMails();
    $this->assertCount(0, $captured_emails, 'The captured emails queue is empty.');

    $this->drupalGet('/mailer-transport-capture-test/send-mail');
    $this->submitForm([], 'Send Mail');

    // Ensure that there is one email in the captured emails array.
    $captured_emails = $this->getMails();

    $this->assertCount(1, $captured_emails, 'One email was captured.');
  }

  /**
   * Sets up mail capture transport.
   */
  protected function setupMailCaptureTransport(): void {
    // Sets up mail capture transport in the child site.
    $this->config('system.mail')
      ->set('mailer_dsn', 'drupal.test-mail://default')
      ->save();

    // Sets up mail capture transport in the test runner.
    $GLOBALS['config']['system.mail']['mailer_dsn'] = 'drupal.test-mail://default';
  }

  /**
   * Gets an array containing all emails sent during this test case.
   *
   * @return \Symfony\Component\Mime\Email[]
   *   An array containing email messages captured during the current test.
   *
   * @see \Drupal\Core\Test\Mailer\Transport\TestMailTransport
   */
  protected function getMails(): array {
    return $this->container->get('state')->get('system.test_mail_transport', []);
  }

}
