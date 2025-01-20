<?php

declare(strict_types=1);

namespace Drupal\Tests\block\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;

/**
 * Tests the JavaScript functionality of the block add filter.
 *
 * @group block
 */
class BlockFilterTest extends WebDriverTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Blocks to be installed on filter block layout  test.
   *
   * @var array[]
   */
  protected $blocks = [
    'header' => [
      'search_form_block',
      'system_branding_block',
    ],
    'highlighted ' => [
      'shortcuts',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'administer blocks',
    ]);

    $this->drupalLogin($admin_user);
  }

  /**
   * Tests block filter.
   */
  public function testBlockFilter(): void {
    $this->drupalGet('admin/structure/block');
    $assertSession = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    // Find the block filter field on the add-block dialog.
    $page->find('css', '#edit-blocks-region-header-title-link')->click();
    $filter = $assertSession->waitForElement('css', '.block-filter-text');

    // Get all block rows, for assertions later.
    $block_rows = $page->findAll('css', '.block-add-table tbody tr');
    // Test block filter reduces the number of visible rows.
    $filter->setValue('ad');
    $session->wait(10000, 'jQuery("#drupal-live-announce").html().indexOf("blocks are available") > -1');
    $visible_rows = $this->filterVisibleElements($block_rows);
    if (count($block_rows) > 0) {
      $this->assertNotSameSize($block_rows, $visible_rows);
    }

    // Test Drupal.announce() message when multiple matches are expected.
    $expected_message = count($visible_rows) . ' blocks are available in the modified list.';
    $this->assertAnnounceContains($expected_message);

    // Test Drupal.announce() message when only one match is expected.
    $filter->setValue('Powered by');
    $session->wait(10000, 'jQuery("#drupal-live-announce").html().indexOf("block is available") > -1');
    $visible_rows = $this->filterVisibleElements($block_rows);
    $this->assertCount(1, $visible_rows);
    $expected_message = '1 block is available in the modified list.';
    $this->assertAnnounceContains($expected_message);

    // Test Drupal.announce() message when no matches are expected.
    $filter->setValue('Pan-Galactic Gargle Blaster');
    $session->wait(10000, 'jQuery("#drupal-live-announce").html().indexOf("0 blocks are available") > -1');
    $visible_rows = $this->filterVisibleElements($block_rows);
    $this->assertCount(0, $visible_rows);
    $expected_message = '0 blocks are available in the modified list.';
    $this->assertAnnounceContains($expected_message);
  }

  /**
   * Test block filter on block layout page.
   */
  public function testRegionsBlockFilter() {
    $defaultTheme = $this->config('system.theme')->get('default');
    $this->container->get('theme_installer')->install(['olivero']);
    $this->config('system.theme')->set('default', 'olivero')->save();
    // Empty message displayed when the type doesn't match with blocks.
    $emptyMessage = 'There are no blocks matching the filter conditions.';

    $blockConfig = [];
    foreach ($this->blocks as $region => $blocks) {
      foreach ($blocks as $blockId) {
        $blockEntity = $this->placeBlock($blockId, ['region' => $region]);
        $humanRegion = ucwords(str_replace('_', ' ', $region));
        $blockConfig[$blockId] = [
          'region' => $humanRegion,
          'label' => $blockEntity->label(),
        ];
      }
    }

    $this->getSession()->resizeWindow(1024, 2048);
    $this->drupalGet('admin/structure/block');
    $assertSession = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    $inputFilter = $page->find('css', '[data-drupal-selector="edit-search-blocks"]');
    $allBlocks = $page->findAll('css', '#blocks tbody tr.draggable');
    $inputFilter->setValue('this text cant be found');
    $this->assertSession()->waitForText($emptyMessage);

    // Text if any block was displayed.
    $visibleBlocks = $this->filterVisibleElements($allBlocks);
    $assertSession->assert(count($visibleBlocks) === 0, "Some blocks has been displayed but should not");

    // Change filter value to found one block specific.
    $inputFilter->setValue($blockConfig['search_form_block']['label']);
    $this->assertSession()->waitForText('Go to items found.');

    // Test if the message disappear.
    $assertSession->pageTextNotContains($emptyMessage);
    $assertSession->assert(
      count($this->filterVisibleElements($allBlocks)) === 1,
      "Only the block {$blockConfig['search_form_block']['label']} should appear, but more than one appeared"
    );
    $assertSession->pageTextContains($blockConfig['search_form_block']['label']);
    $assertSession->pageTextContains($blockConfig['search_form_block']['region']);

    // Search by another word that doesn't exist.
    // And check if the empty appear once.
    $inputFilter->setValue('string test');
    $this->assertSession()->waitForElement('css', '#block-filter-region-empty-message');
    $assertSession->pageTextContainsOnce($emptyMessage);

    // Test each block validating if the regions will be displayed.
    foreach ($blockConfig as $blockTest) {
      $inputFilter->setValue($blockTest['label']);
      $this->assertSession()
        ->waitForElementVisible('xpath', "//td[contains(text(), '" . $blockTest['label'] . "')]");
      $assertSession->pageTextContains($blockTest['label']);
      $assertSession->pageTextContains($blockTest['region']);
    }

    // Test drag and drop after any filter applied.
    $inputFilter->setValue('');
    $this->moveBlock('olivero-messages', 'highlighted');
    $this->assertBlockOnRegion('olivero-messages', 'highlighted');

    // Test filter when user changes the region by select element.
    $this->getSession()
      ->getPage()
      ->findField('edit-blocks-olivero-messages-region')
      ->setValue('breadcrumb');
    $this->assertSession()
      ->waitForElementVisible('css', '#blocks tbody tr[data-drupal-selector="edit-blocks-olivero-messages"] a.tabledrag-handle');

    $this->assertEquals(
      'breadcrumb',
      $this->getSession()->getPage()->findField('edit-blocks-olivero-messages-region')->getValue(),
      "Drupal search form block should be positioned on left sidebar"
    );

    $this->moveBlock('olivero-primary-local-tasks', 'highlighted');
    $this->moveBlock('olivero-powered', 'highlighted');
    $this->moveBlock('olivero-account-menu', 'breadcrumb');

    $this->assertBlockOnRegion('olivero-primary-local-tasks', 'highlighted');
    $this->assertBlockOnRegion('olivero-powered', 'highlighted');
    $this->assertBlockOnRegion('olivero-account-menu', 'breadcrumb');

    // Test toggle blocks region.
    $inputFilter->setValue('account');
    $toggleHighlightedBlocks = $this->getSession()
      ->getPage()
      ->findById('edit-blocks-region-highlighted-title-filter');
    $this->assertEquals($toggleHighlightedBlocks->getValue(), 'Show filtered');
    $toggleHighlightedBlocks->focus();
    $toggleHighlightedBlocks->click();
    $this->assertEquals($toggleHighlightedBlocks->getValue(), 'Hide filtered');
    $this->assertFalse($page->find('css', 'tr[data-drupal-selector="edit-blocks-region-highlighted-filter"]')
      ->isVisible());

    // Back to the previous theme default to avoid failing other tests.
    $this->config('system.theme')->set('default', $defaultTheme)->save();
  }

  /**
   * Removes any non-visible elements from the passed array.
   *
   * @param \Behat\Mink\Element\NodeElement[] $elements
   *   An array of node elements.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The node element.
   */
  protected function filterVisibleElements(array $elements): array {
    $elements = array_filter($elements, function (NodeElement $element) {
      return $element->isVisible();
    });
    return $elements;
  }

  /**
   * Checks for inclusion of text in #drupal-live-announce.
   *
   * @param string $expected_message
   *   The text expected to be present in #drupal-live-announce.
   *
   * @internal
   */
  protected function assertAnnounceContains(string $expected_message): void {
    $assert_session = $this->assertSession();
    $this->assertNotEmpty($assert_session->waitForElement('css', "#drupal-live-announce:contains('$expected_message')"));
  }

  /**
   * Check if the block is in the correct region.
   *
   * @param string $blockId
   *   The block to used to find the element.
   * @param string $regionExpected
   *   Region expected by the block.
   *
   * @internal
   */
  protected function assertBlockOnRegion(string $blockId, string $regionExpected): void {
    $selectElement = $this->getSession()
      ->getPage()
      ->findField('edit-blocks-' . $blockId . '-region');

    $trFromSelect = $selectElement->getParent()
      ->getParent()
      ->getParent();

    // Test if the select element was updated.
    $this->assertEquals(
      $regionExpected,
      $selectElement->getValue(),
      'Select value should be ' . $regionExpected . ' but ' . $selectElement->getValue() . ' found.'
    );

    // Test data parent element was updated.
    $this->assertEquals(
      $regionExpected,
      $trFromSelect->getAttribute('data-parent-region'),
      'Data parent-region should be ' . $regionExpected . ' but ' . $trFromSelect->getAttribute('data-parent-region') . ' found.'
    );

    // To make sure that element was positioned in the correct place.
    $previousRegionElement = $trFromSelect->find('xpath', 'preceding-sibling::tr[contains(@class, "region-title")][1]');
    $this->assertEquals(
      $regionExpected,
      $previousRegionElement->getAttribute('data-region'),
      'The previous tr region of the element should be ' . $regionExpected . '. ' . $previousRegionElement->getAttribute('region') . ' found.'
    );
  }

  /**
   * Move blocks dragging or selecting from the region to the other.
   *
   * @param string $blockId
   *   The block to be moved.
   * @param string $dest
   *   The destination region.
   */
  protected function moveBlock(string $blockId, string $dest): void {
    $destRegion = $this->getSession()
      ->getPage()
      ->find('css', 'tr[data-parent-region="' . $dest . '"]');

    $dragRow = '#blocks tbody tr[data-drupal-selector="edit-blocks-' . $blockId . '"] a.tabledrag-handle';
    $blockToMove = $this->getSession()
      ->getPage()
      ->find('css', $dragRow);

    $javascript = <<<JS
      document.querySelector("tr[data-drupal-selector='edit-blocks-{$blockId}']").scrollIntoViewIfNeeded();
JS;
    $this->getSession()
      ->executeScript($javascript);

    $blockToMove->dragTo($destRegion);

    $this->assertSession()
      ->waitForElementVisible('css', 'tr[data-drupal-selector="edit-blocks-' . $blockId . '"].drag-previous');
  }

}
