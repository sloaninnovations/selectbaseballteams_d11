<?php

namespace Drupal\mailer_transport_capture_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

/**
 * Mailer transport send mail test form.
 */
class MailerTransportTestForm extends FormBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('mailer.transports'));
  }

  /**
   * Constructs the mailer transport test controller.
   */
  public function __construct(protected TransportInterface $mailerTransport) {
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailer_transport_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => 'Send Mail',
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = new Email();
    $email->subject('Test message')
      ->from('test@localhost.localdomain')
      ->text('Hello test runner!');

    $this->mailerTransport->send($email->to('admin@localhost.localdomain'));
  }

}
