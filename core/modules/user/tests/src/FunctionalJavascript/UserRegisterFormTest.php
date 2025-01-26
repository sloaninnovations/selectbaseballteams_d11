<?php

declare(strict_types=1);

namespace Drupal\Tests\user\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests user registration forms via JS.
 *
 * @group user
 */
class UserRegisterFormTest extends WebDriverTestBase {

  /**
   * DocumentElement object.
   *
   * @var \Behat\Mink\Element\DocumentElement
   */
  protected $page;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->page = $this->getSession()->getPage();
  }

  /**
   * Tests if registration form writes to localStorage.
   */
  public function testRegistrationFormStorage(): void {

    // Load register form.
    $this->drupalGet('user/register');

    // Register user.
    $name = $this->randomMachineName();
    $this->page->fillField('edit-name', $name);
    $this->page->fillField('edit-mail', $name . '@example.com');
    $this->page->pressButton('edit-submit');

    // Test if localStorage is set now.
    $this->assertJsCondition("localStorage.getItem('Drupal.visitor.name') === null", 10000, 'Failed to assert that the visitor name was not written to localStorage.');

  }

}
