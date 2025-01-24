<?php

declare(strict_types=1);

namespace Drupal\Tests\datetime_range\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\BasicStringFormatter;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeDefaultFormatter;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests that the usage of FormatterPluginManager is correct in all cases.
 *
 * @group API
 * @group datetime_range
 */
class FormatterPluginManagerImplementationTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $formatter_plugin_manager = $this->getMockBuilder(FormatterPluginManager::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getDefinitions'])
      ->getMock();

    $plugin_definitions = [
      'basic_string' => [
        'class' => BasicStringFormatter::class,
        'provider' => 'core',
        'id' => 'basic_string',
        'label' => 'Plain text',
        'description' => NULL,
        'field_types' => ['string_long', 'email'],
        'weight' => NULL,
      ],
      'daterange_default' => [
        'class' => DateRangeDefaultFormatter::class,
        'provider' => 'datetime_range',
        'id' => 'daterange_default',
        'label' => 'Default',
        'description' => NULL,
        'field_types' => ['daterange'],
        'weight' => NULL,
      ],
    ];
    // Use reflection to inject definitions variable.
    // This way we can keep all the other code unmocked.
    $reflection_definitions = new \ReflectionProperty(FormatterPluginManager::class, 'definitions');
    $formatter_plugin_manager->method('getDefinitions')->willReturnCallback(function () use ($plugin_definitions, $reflection_definitions, $formatter_plugin_manager) {
        $reflection_definitions->setValue($formatter_plugin_manager, $plugin_definitions);
        return $plugin_definitions;
    });

    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.formatter', $formatter_plugin_manager);
    \Drupal::setContainer($container);

    // Testing module file which isn't autoloaded in unit tests - load here once.
    require_once __DIR__ . '/../../../datetime_range.module';
  }

  /**
   * @param array $components
   *   List of components to return.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function getDisplayMock(array $components): EntityViewDisplay|MockObject {
    $display = $this->createMock('Drupal\Core\Entity\Entity\EntityViewDisplay');
    $display->expects($this->any())
      ->method('getComponents')
      ->will($this->returnValue($components));
    $display->expects($this->any())->method('getComponent')
      ->will(
        $this->returnValueMap([array_keys($components), array_values($components)])
      );
    return $display;
  }

  /**
   * @covers ::datetime_range_entity_view_display_presave
   */
  public function testNoComponents(): void {
    $components = [];
    $display = $this->getDisplayMock($components);

    // No components, no adjustments, no exceptions thrown,
    $display->expects($this->never())->method('setComponent');
    datetime_range_entity_view_display_presave($display);
  }

  /**
   * @covers ::datetime_range_entity_view_display_presave
   */
  public function testNoDateRangeComponents(): void {
    $components = [
      'string_dummy' => ['type' => 'basic_string'],
    ];
    $display = $this->getDisplayMock($components);

    // No date_range component, no adjustments, no exceptions thrown.
    $display->expects($this->never())->method('setComponent');
    datetime_range_entity_view_display_presave($display);
  }

  /**
   * @covers ::datetime_range_entity_view_display_presave
   */
  public function testDateRangeComponents(): void {
    $components = [
      'string_dummy' => ['type' => 'basic_string'],
      'date_range_dummy' => ['type' => 'daterange_default'],
    ];
    $display = $this->getDisplayMock($components);

    // Single date_range component, single call, no exceptions thrown.
    $display->expects($this->once())->method('setComponent');
    datetime_range_entity_view_display_presave($display);
  }

  /**
   * Test that display components without a related plugins won't lead to errors.
   *
   * @covers ::datetime_range_entity_view_display_presave
   */
  public function testComponentsWithoutPluginDefinition(): void {
    $components = [
      'non_plugin_type' => ['type' => 'non_plugin_type'],
      'date_range_dummy' => ['type' => 'daterange_default'],
    ];
    $display = $this->getDisplayMock($components);

    // Single date_range component, single call, no exceptions thrown - despite
    // non plugin type.
    $display->expects($this->once())->method('setComponent');

    $anExceptionWasThrown = FALSE;
    try {
      datetime_range_entity_view_display_presave($display);
    }
    catch (\Throwable) {
      $anExceptionWasThrown = TRUE;
    }

    $this->assertFalse($anExceptionWasThrown, "Exception was thrown when it shouldn't have been");
  }

}
