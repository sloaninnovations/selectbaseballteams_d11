<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional;

/**
 * Tests the Layout Builder UI with blocks.
 *
 * @group layout_builder
 */
class LayoutBuilderBlocksTest extends LayoutBuilderTestBase {

  /**
   * Tests the usage of placeholders for empty blocks.
   *
   * @see \Drupal\Core\Render\PreviewFallbackInterface::getPreviewFallbackString()
   * @see \Drupal\layout_builder\EventSubscriber\BlockComponentRenderArray::onBuildRender()
   */
  public function testBlockPlaceholder(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');

    // Customize the default view mode.
    $this->drupalGet("$field_ui_prefix/display/default/layout");

    // Add a block whose content is controlled by state and is empty by default.
    $this->clickLink('Add block');
    $this->clickLink('Test block caching');
    $page->fillField('settings[label]', 'The block label');
    $page->pressButton('Add block');

    $block_content = 'I am content';
    $placeholder_content = 'Placeholder for the "The block label" block';

    // The block placeholder is displayed and there is no content.
    $assert_session->pageTextContains($placeholder_content);
    $assert_session->pageTextNotContains($block_content);

    // Set block content and reload the page.
    \Drupal::state()->set('block_test.content', $block_content);
    $this->getSession()->reload();

    // The block placeholder is no longer displayed and the content is visible.
    $assert_session->pageTextNotContains($placeholder_content);
    $assert_session->pageTextContains($block_content);
  }

}
