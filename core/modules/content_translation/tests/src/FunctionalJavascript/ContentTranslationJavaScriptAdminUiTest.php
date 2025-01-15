<?php

declare(strict_types=1);

namespace Drupal\Tests\content_translation\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests that Content language and translation JS admin UI is not broken.
 *
 * @group content_translation
 */
class ContentTranslationJavaScriptAdminUiTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['content_translation'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * Tests that a contextual link is available for translating a node.
   *
   * This test used the standard profile because it needs the content language
   * admin page to have as more "Custom language settings" checkboxes as
   * possible to have more surface for a possible JS error.
   */
  public function testContentTranslationJavaScriptAdminUI() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/config/regional/content-language');
    $this->failOnJavaScriptErrors();
  }

}
