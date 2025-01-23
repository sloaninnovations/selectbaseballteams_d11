<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Form;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\system\Form\SiteMaintenanceModeForm;

/**
 * Tests the maintenance mode form submission behavior.
 *
 * @group Form
 */
class SiteMaintenanceModeFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'language'];

  /**
   * The logger spy object for this test class.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $loggerSpy;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install system config.
    $this->installConfig(['system']);

    // Configure default language.
    \Drupal::service('language.default')->set(
      ConfigurableLanguage::createFromLangcode('en')
    );

    // Create a logger spy object.
    $this->loggerSpy = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory
      ->method('get')
      ->willReturn($this->loggerSpy);
    $this->container->set('logger.factory', $loggerFactory);

  }

  /**
   * Tests the logging of the maintenance mode being set/unset.
   */
  public function testMessageIsLoggedWhenMaintenanceModeIsSet(): void {

    // Configure the logger spy object to check for maintenance mode
    // logged messages via any of the allowed log methods.
    $allowedMethods = ['warning', 'info', 'notice'];
    $this->loggerSpy->expects($invocationsSpy = $this->any())
      ->method($this->callback(function ($method) use ($allowedMethods) {
        return in_array($method, $allowedMethods, TRUE);
      }))
      ->with($this->callback(function ($message) {
        if (is_string($message)) {
          return str_contains(strtolower($message), 'maintenance');
        }
        if ($message instanceof TranslatableMarkup) {
          return str_contains(strtolower($message->getUntranslatedString()), 'maintenance');
        }
        return FALSE;
      }));

    // Build the site maintenance form to test.
    $form_object = \Drupal::service('class_resolver')->getInstanceFromDefinition(SiteMaintenanceModeForm::class);
    $form_state = new FormState();
    $form = \Drupal::formBuilder()->buildForm($form_object, $form_state);

    // Set maintenance mode on. A message should be logged.
    $form_state->setValue('maintenance_mode', 1);
    $form_object->submitForm($form, $form_state);
    $this->assertEquals(1, $invocationsSpy->numberOfInvocations());

    // Set maintenance mode on again. A message shouldn't be logged again.
    $form_state->setValue('maintenance_mode', 1);
    $form_object->submitForm($form, $form_state);
    $this->assertEquals(1, $invocationsSpy->numberOfInvocations());

    // Set maintenance mode off. A second message should be logged.
    $form_state->setValue('maintenance_mode', 0);
    $form_object->submitForm($form, $form_state);
    $this->assertEquals(2, $invocationsSpy->numberOfInvocations());

    // Set maintenance mode of again. A message shouldn't be logged again.
    $form_state->setValue('maintenance_mode', 0);
    $form_object->submitForm($form, $form_state);
    $this->assertEquals(2, $invocationsSpy->numberOfInvocations());
  }

}
