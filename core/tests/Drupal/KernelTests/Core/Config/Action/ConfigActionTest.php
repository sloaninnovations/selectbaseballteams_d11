<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config\Action;

// cspell:ignore inflector
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Uuid\Uuid;
use Drupal\config_test\ConfigActionErrorEntity\DuplicatePluralizedMethodName;
use Drupal\config_test\ConfigActionErrorEntity\DuplicatePluralizedOtherMethodName;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\DuplicateConfigActionIdException;
use Drupal\Core\Config\Action\EntityMethodException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the config action system.
 *
 * @group config
 */
class ConfigActionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test', 'block', 'system', 'path_alias'];

  /**
   * Reference valid configuration for placing a block.
   */
  protected $validBlock = [
    'theme' => 'olivero',
    'region' => 'content',
    'weight' => 0,
    'provider' => NULL,
    'id' => 'config_action_test',
    'plugin' => 'local_tasks_block',
    'settings' => [
      'id' => 'local_tasks_block',
      'label' => 'Additional tabs',
      'provider' => 'core',
      'primary' => FALSE,
      'secondary' => TRUE,
    ],
    'visibility' => [],
  ];

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\EntityCreate
   */
  public function testEntityCreate(): void {
    $this->assertCount(0, \Drupal::entityTypeManager()->getStorage('config_test')->loadMultiple(), 'There are no config_test entities');
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    $manager->applyAction('entity_create:createIfNotExists', 'config_test.dynamic.action_test', ['label' => 'Action test']);
    /** @var \Drupal\config_test\Entity\ConfigTest[] $config_test_entities */
    $config_test_entities = \Drupal::entityTypeManager()->getStorage('config_test')->loadMultiple();
    $this->assertCount(1, \Drupal::entityTypeManager()->getStorage('config_test')->loadMultiple(), 'There is 1 config_test entity');
    $this->assertSame('Action test', $config_test_entities['action_test']->label());
    $this->assertTrue(Uuid::isValid((string) $config_test_entities['action_test']->uuid()), 'Config entity assigned a valid UUID');

    // Calling createIfNotExists action again will not error.
    $manager->applyAction('entity_create:createIfNotExists', 'config_test.dynamic.action_test', ['label' => 'Action test']);

    try {
      $manager->applyAction('entity_create:create', 'config_test.dynamic.action_test', ['label' => 'Action test']);
      $this->fail('Expected exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Entity config_test.dynamic.action_test exists', $e->getMessage());
    }
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\EntityMethod
   */
  public function testEntityMethod(): void {
    $this->installConfig('config_test');
    $storage = \Drupal::entityTypeManager()->getStorage('config_test');

    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame('Default', $config_test_entity->getProtectedProperty());

    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    // Call a method action.
    $manager->applyAction('entity_method:config_test.dynamic:setProtectedProperty', 'config_test.dynamic.dotted.default', 'Test value');
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame('Test value', $config_test_entity->getProtectedProperty());

    $manager->applyAction('entity_method:config_test.dynamic:setProtectedProperty', 'config_test.dynamic.dotted.default', 'Test value 2');
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame('Test value 2', $config_test_entity->getProtectedProperty());

    $manager->applyAction('entity_method:config_test.dynamic:concatProtectedProperty', 'config_test.dynamic.dotted.default', ['Test value ', '3']);
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame('Test value 3', $config_test_entity->getProtectedProperty());

    $manager->applyAction('entity_method:config_test.dynamic:concatProtectedPropertyOptional', 'config_test.dynamic.dotted.default', ['Test value ', '4']);
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame('Test value 4', $config_test_entity->getProtectedProperty());

    // Test calling an action that has 2 arguments but one is optional with an
    // array value.
    $manager->applyAction('entity_method:config_test.dynamic:concatProtectedPropertyOptional', 'config_test.dynamic.dotted.default', ['Test value 5']);
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame('Test value 5', $config_test_entity->getProtectedProperty());

    // Test calling an action that has 2 arguments but one is optional with a
    // non array value.
    $manager->applyAction('entity_method:config_test.dynamic:concatProtectedPropertyOptional', 'config_test.dynamic.dotted.default', 'Test value 6');
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame('Test value 6', $config_test_entity->getProtectedProperty());

    // Test calling an action that expects no arguments.
    $manager->applyAction('entity_method:config_test.dynamic:defaultProtectedProperty', 'config_test.dynamic.dotted.default', []);
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame('Set by method', $config_test_entity->getProtectedProperty());

    $manager->applyAction('entity_method:config_test.dynamic:addToArray', 'config_test.dynamic.dotted.default', 'foo');
    $manager->applyAction('entity_method:config_test.dynamic:addToArray', 'config_test.dynamic.dotted.default', 'bar');
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame(['foo', 'bar'], $config_test_entity->getArrayProperty());

    $manager->applyAction('entity_method:config_test.dynamic:addToArray', 'config_test.dynamic.dotted.default', ['a', 'b', 'c']);
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame(['foo', 'bar', ['a', 'b', 'c']], $config_test_entity->getArrayProperty());

    $manager->applyAction('entity_method:config_test.dynamic:setArray', 'config_test.dynamic.dotted.default', ['a', 'b', 'c']);
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame(['a', 'b', 'c'], $config_test_entity->getArrayProperty());

    $manager->applyAction('entity_method:config_test.dynamic:setArray', 'config_test.dynamic.dotted.default', [['a', 'b', 'c'], ['a']]);
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame([['a', 'b', 'c'], ['a']], $config_test_entity->getArrayProperty());

    $config_test_entity->delete();
    try {
      $manager->applyAction('entity_method:config_test.dynamic:setProtectedProperty', 'config_test.dynamic.dotted.default', 'Test value');
      $this->fail('Expected exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Entity config_test.dynamic.dotted.default does not exist', $e->getMessage());
    }

    // Test custom and default admin labels.
    $this->assertSame('Test configuration append', (string) $manager->getDefinition('entity_method:config_test.dynamic:append')['admin_label']);
    $this->assertSame('Set default name', (string) $manager->getDefinition('entity_method:config_test.dynamic:defaultProtectedProperty')['admin_label']);
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\EntityMethod
   */
  public function testPluralizedEntityMethod(): void {
    $this->installConfig('config_test');
    $storage = \Drupal::entityTypeManager()->getStorage('config_test');

    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    // Call a pluralized method action.
    $manager->applyAction('entity_method:config_test.dynamic:addToArrayMultipleTimes', 'config_test.dynamic.dotted.default', ['a', 'b', 'c', 'd']);
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame(['a', 'b', 'c', 'd'], $config_test_entity->getArrayProperty());

    $manager->applyAction('entity_method:config_test.dynamic:addToArrayMultipleTimes', 'config_test.dynamic.dotted.default', [['foo'], 'bar']);
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame(['a', 'b', 'c', 'd', ['foo'], 'bar'], $config_test_entity->getArrayProperty());

    $config_test_entity->setProtectedProperty('')->save();
    $manager->applyAction('entity_method:config_test.dynamic:appends', 'config_test.dynamic.dotted.default', ['1', '2', '3']);
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame('123', $config_test_entity->getProtectedProperty());

    // Test that the inflector converts to a good plural form.
    $config_test_entity->setProtectedProperty('')->save();
    $manager->applyAction('entity_method:config_test.dynamic:concatProtectedProperties', 'config_test.dynamic.dotted.default', [['1', '2'], ['3', '4']]);
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('dotted.default');
    $this->assertSame('34', $config_test_entity->getProtectedProperty());

    $this->assertTrue($manager->hasDefinition('entity_method:config_test.dynamic:setProtectedProperty'), 'The setProtectedProperty action exists');
    // cspell:ignore Propertys
    $this->assertFalse($manager->hasDefinition('entity_method:config_test.dynamic:setProtectedPropertys'), 'There is no automatically pluralized version of the setProtectedProperty action');

    // Admin label for pluralized form.
    $this->assertSame('Test configuration append (multiple calls)', (string) $manager->getDefinition('entity_method:config_test.dynamic:appends')['admin_label']);
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\EntityMethod
   */
  public function testPluralizedEntityMethodException(): void {
    $this->installConfig('config_test');
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    $this->expectException(EntityMethodException::class);
    $this->expectExceptionMessage('The pluralized entity method config action \'entity_method:config_test.dynamic:addToArrayMultipleTimes\' requires an array value in order to call Drupal\config_test\Entity\ConfigTest::addToArray() multiple times');
    $manager->applyAction('entity_method:config_test.dynamic:addToArrayMultipleTimes', 'config_test.dynamic.dotted.default', 'Test value');
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\Deriver\EntityMethodDeriver
   */
  public function testDuplicatePluralizedMethodNameException(): void {
    \Drupal::state()->set('config_test.class_override', DuplicatePluralizedMethodName::class);
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    $this->installConfig('config_test');
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    $this->expectException(EntityMethodException::class);
    $this->expectExceptionMessage('Duplicate action can not be created for ID \'config_test.dynamic:testMethod\' for Drupal\config_test\ConfigActionErrorEntity\DuplicatePluralizedMethodName::testMethod(). The existing action is for the ::testMethod() method');
    $manager->getDefinitions();
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\Deriver\EntityMethodDeriver
   */
  public function testDuplicatePluralizedOtherMethodNameException(): void {
    \Drupal::state()->set('config_test.class_override', DuplicatePluralizedOtherMethodName::class);
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    $this->installConfig('config_test');
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    $this->expectException(EntityMethodException::class);
    $this->expectExceptionMessage('Duplicate action can not be created for ID \'config_test.dynamic:testMethod2\' for Drupal\config_test\ConfigActionErrorEntity\DuplicatePluralizedOtherMethodName::testMethod2(). The existing action is for the ::testMethod() method');
    $manager->getDefinitions();
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\EntityMethod
   */
  public function testEntityMethodException(): void {
    $this->installConfig('config_test');
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    $this->expectException(EntityMethodException::class);
    $this->expectExceptionMessage('Entity method config action \'entity_method:config_test.dynamic:concatProtectedProperty\' requires an array value. The number of parameters or required parameters for Drupal\config_test\Entity\ConfigTest::concatProtectedProperty() is not 1');
    $manager->applyAction('entity_method:config_test.dynamic:concatProtectedProperty', 'config_test.dynamic.dotted.default', 'Test value');
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\SimpleConfigUpdate
   */
  public function testSimpleConfigUpdate(): void {
    $this->installConfig('config_test');
    $this->assertSame('bar', $this->config('config_test.system')->get('foo'));

    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    // Call the simple config update action.
    $manager->applyAction('simpleConfigUpdate', 'config_test.system', ['foo' => 'Yay!']);
    $this->assertSame('Yay!', $this->config('config_test.system')->get('foo'));

    try {
      $manager->applyAction('simpleConfigUpdate', 'config_test.system', 'Test');
      $this->fail('Expected exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Config config_test.system can not be updated because $value is not an array', $e->getMessage());
    }

    $this->config('config_test.system')->delete();
    try {
      $manager->applyAction('simpleConfigUpdate', 'config_test.system', ['foo' => 'Yay!']);
      $this->fail('Expected exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Config config_test.system does not exist so can not be updated', $e->getMessage());
    }
  }

  /**
   * @see \Drupal\Core\Config\Action\ConfigActionManager::getShorthandActionIdsForEntityType()
   */
  public function testShorthandActionIds(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('config_test');
    $this->assertCount(0, $storage->loadMultiple(), 'There are no config_test entities');
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    $manager->applyAction('createIfNotExists', 'config_test.dynamic.action_test', ['label' => 'Action test', 'protected_property' => '']);
    /** @var \Drupal\config_test\Entity\ConfigTest[] $config_test_entities */
    $config_test_entities = $storage->loadMultiple();
    $this->assertCount(1, $config_test_entities, 'There is 1 config_test entity');
    $this->assertSame('Action test', $config_test_entities['action_test']->label());

    $this->assertSame('', $config_test_entities['action_test']->getProtectedProperty());

    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    // Call a method action.
    $manager->applyAction('setProtectedProperty', 'config_test.dynamic.action_test', 'Test value');
    /** @var \Drupal\config_test\Entity\ConfigTest $config_test_entity */
    $config_test_entity = $storage->load('action_test');
    $this->assertSame('Test value', $config_test_entity->getProtectedProperty());
  }

  /**
   * @see \Drupal\Core\Config\Action\ConfigActionManager::getShorthandActionIdsForEntityType()
   */
  public function testDuplicateShorthandActionIds(): void {
    $this->enableModules(['config_action_duplicate_test']);
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    $this->expectException(DuplicateConfigActionIdException::class);
    $this->expectExceptionMessage("The plugins 'entity_method:config_test.dynamic:setProtectedProperty' and 'config_action_duplicate_test:config_test.dynamic:setProtectedProperty' both resolve to the same shorthand action ID for the 'config_test' entity type");
    $manager->applyAction('createIfNotExists', 'config_test.dynamic.action_test', ['label' => 'Action test', 'protected_property' => '']);
  }

  /**
   * @see \Drupal\Core\Config\Action\ConfigActionManager::getShorthandActionIdsForEntityType()
   */
  public function testParentAttributes(): void {
    $definitions = $this->container->get('plugin.manager.config_action')->getDefinitions();
    // The \Drupal\config_test\Entity\ConfigQueryTest::concatProtectedProperty()
    // does not have an attribute but the parent does so this is discovered.
    $this->assertArrayHasKey('entity_method:config_test.query:concatProtectedProperty', $definitions);
  }

  /**
   * @see \Drupal\Core\Config\Action\ConfigActionManager
   */
  public function testMissingAction(): void {
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessageMatches('/^The "does_not_exist" plugin does not exist/');
    $this->container->get('plugin.manager.config_action')->applyAction('does_not_exist', 'config_test.system', ['foo' => 'Yay!']);
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\PlaceBlock
   */
  public function testPlaceNull(): void {
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    // Empty configuration should trigger an error.
    try {
      $manager->applyAction('placeBlock', 'block.block.block_test', NULL);
      $this->fail('Expected exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Block block.block.block_test cannot be created because no configuration was provided', $e->getMessage());
    }
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\PlaceBlock
   */
  public function testPlaceNonArray(): void {
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    // Empty configuration should trigger an error.
    try {
      $manager->applyAction('placeBlock', 'block.block.block_test', 'Dummy string');
      $this->fail('Expected exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Block block.block.block_test cannot be created because provided configuration is not an array', $e->getMessage());
    }
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\PlaceBlock
   */
  public function testPlaceNonBlock(): void {
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    // Non-block configuration should trigger an error.
    try {
      $manager->applyAction('placeBlock', 'config_test.dynamic.action_test', ['label' => 'Action test', 'protected_property' => '']);
      $this->fail('Expected exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Provided config entity config_test.dynamic.action_test is not a block', $e->getMessage());
    }
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\PlaceBlock
   */
  public function testPlaceNoTheme(): void {
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');

    // Block configuration with no theme specified should trigger an error.
    $no_theme = $this->validBlock;
    unset($no_theme['theme']);
    try {
      $manager->applyAction('placeBlock', 'block.block.no_theme', $no_theme);
      $this->fail('Expected exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Block block.block.no_theme cannot be created because of missing theme identifier', $e->getMessage());
    }
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\PlaceBlock
   */
  public function testPlaceInvalidTheme(): void {
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    // Block configuration with invalid theme specified should trigger an error.
    $bad_theme = $this->validBlock;
    $bad_theme['theme'] = 'mongoose';
    try {
      $manager->applyAction('placeBlock', 'block.block.bad_theme', $bad_theme);
      $this->fail('Expected exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Block block.block.bad_theme cannot be created because the specified theme is missing', $e->getMessage());
    }
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\PlaceBlock
   */
  public function testPlaceMissingPlugin(): void {
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    // Block configuration with no plugin specified should trigger an error.
    $no_plugin = $this->validBlock;
    unset($no_plugin['plugin']);
    try {
      $manager->applyAction('placeBlock', 'block.block.no_plugin', $no_plugin);
      $this->fail('Expected exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Block block.block.no_plugin requires a plugin value', $e->getMessage());
    }
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\PlaceBlock
   */
  public function testPlaceBlockRegion(): void {
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    // Tests below require a theme installed.
    \Drupal::service('theme_installer')->install(['olivero']);

    // Block configuration with no region specified should trigger an error.
    $no_region = $this->validBlock;
    unset($no_region['region']);
    try {
      $manager->applyAction('placeBlock', 'block.block.no_region', $no_region);
      $this->fail('Expected exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Block block.block.no_region cannot be created because a region must be specified', $e->getMessage());
    }

    // Block configuration with invalid region specified must trigger an error.
    $bad_region = $this->validBlock;
    $bad_region['id'] = 'bad_region';
    $bad_region['region'] = 'platypus';
    try {
      $manager->applyAction('placeBlock', 'block.block.bad_region', $bad_region);
      $this->fail('Expected exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Block block.block.bad_region could not identify a valid region', $e->getMessage());
    }

    $bad_region['regions'] = ['nonexistent', 'bogus', 'nonsense'];
    try {
      $manager->applyAction('placeBlock', 'block.block.bad_region', $bad_region);
      $this->fail('Expected exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Block block.block.bad_region could not identify a valid region', $e->getMessage());
    }

    // Validate that the block can be placed with a valid backup region.
    $bad_region['regions'][] = 'content';
    $manager->applyAction('placeBlock', 'block.block.bad_region', $bad_region);
    $placed_blocks = \Drupal::entityTypeManager()->getStorage('block')->loadByProperties([
      'id' => 'bad_region',
    ]);
    $this->assertCount(1, $placed_blocks, 'There is 1 matching block entity');
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\PlaceBlock
   */
  public function testPlaceBlockThemes(): void {
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    // Tests below require a theme installed.
    \Drupal::service('theme_installer')->install(['olivero', 'claro']);
    $config = \Drupal::configFactory()->getEditable('system.theme');
    $config->set('default', 'olivero')->save();

    // Validate that the block can be placed with 'default' theme specified.
    $default_theme_block = $this->validBlock;
    $default_theme_block['theme'] = 'default';
    $default_theme_block['id'] = 'default_theme_block';
    $manager->applyAction('placeBlock', 'block.block.default_theme_block', $default_theme_block);
    // ID will be automatically updated based on the default theme name.
    $placed_blocks = \Drupal::entityTypeManager()->getStorage('block')->loadByProperties([
      'id' => 'olivero_theme_block',
    ]);
    $this->assertCount(1, $placed_blocks, 'There is 1 matching block entity');
    $found_block = array_pop($placed_blocks);
    $this->assertSame('olivero', $found_block->get('theme'));

    // Validate that the block can be placed.
    $manager->applyAction('placeBlock', 'block.block.config_action_test', $this->validBlock);
    $placed_blocks = \Drupal::entityTypeManager()->getStorage('block')->loadByProperties([
      'id' => 'config_action_test',
    ]);
    $this->assertCount(1, $placed_blocks, 'There is 1 matching block entity');

    // Placing the same block again should not fail.
    $manager->applyAction('placeBlock', 'block.block.config_action_test', $this->validBlock);

    // Placing the same block again with a different theme should fail.
    $different_theme = $this->validBlock;
    $different_theme['theme'] = 'claro';
    try {
      $manager->applyAction('placeBlock', 'block.block.config_action_test', $different_theme);
      $this->fail('Expected theme exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Unable to place block block.block.config_action_test because a block with this name has been placed in a different theme', $e->getMessage());
    }
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\PlaceBlock
   */
  public function testPlaceBlockOrder(): void {
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    // Tests below require a theme installed.
    \Drupal::service('theme_installer')->install(['olivero']);
    // Validate that placing blocks first and last works as expected.
    $first_block = $last_block = $this->validBlock;
    $last_block['weight'] = 'last';
    $last_block['id'] = 'config_action_last';
    $manager->applyAction('placeBlock', 'block.block.config_action_last', $last_block);
    $first_block['weight'] = 'first';
    $first_block['id'] = 'config_action_first';
    $manager->applyAction('placeBlock', 'block.block.config_action_first', $first_block);
    $placed_blocks = \Drupal::entityTypeManager()->getStorage('block')->loadByProperties([
      'theme' => 'olivero',
      'region' => 'content',
    ]);
    // These would be out of order if not for the weight keywords.
    uasort($placed_blocks, 'Drupal\block\Entity\Block::sort');
    $this->assertSame('config_action_first', array_key_first($placed_blocks));
    $this->assertSame('config_action_last', array_key_last($placed_blocks));
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\PlaceBlock
   */
  public function testPlaceBlockIds(): void {
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    // Tests below require a theme installed.
    \Drupal::service('theme_installer')->install(['olivero']);

    // Validate that the block can be placed without an id, with a valid name.
    $no_id = $this->validBlock;
    unset($no_id['id']);
    // First, verify that it fails without an available fallback.
    try {
      $manager->applyAction('placeBlock', 'block.block.', $no_id);
      $this->fail('Expected exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Unable to determine a valid id for block block.block.', $e->getMessage());
    }
    // Next, verify that it can extract an id from the config name.
    $manager->applyAction('placeBlock', 'block.block.no_id', $no_id);
    $placed_blocks = \Drupal::entityTypeManager()->getStorage('block')->loadByProperties([
      'id' => 'no_id',
    ]);
    $this->assertCount(1, $placed_blocks, 'There is 1 matching block entity');
  }

  /**
   * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\PlaceBlock
   */
  public function testPlaceBlockExisting(): void {
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    // Tests below require a theme installed.
    \Drupal::service('theme_installer')->install(['olivero']);

    // The following tests require the block to be placed already.
    $manager->applyAction('placeBlock', 'block.block.config_action_test', $this->validBlock);

    // Placing the same block again with a different plugin should fail.
    $different_plugin = $this->validBlock;
    $different_plugin['plugin'] = 'some_other_plugin';
    try {
      $manager->applyAction('placeBlock', 'block.block.config_action_test', $different_plugin);
      $this->fail('Expected plugin exception not thrown');
    }
    catch (ConfigActionException $e) {
      $this->assertSame('Unable to place block block.block.config_action_test because a block with this name has been placed but uses a different plugin', $e->getMessage());
    }

    // Placing the same block again with new visibility or settings should
    // update the block.
    $updated_block = $this->validBlock;
    $updated_block['visibility'] = [
      'request_path' => [
        'id' => 'request_path',
        'negate' => FALSE,
        'pages' => '<front>',
      ],
    ];
    $updated_block['settings']['primary'] = TRUE;
    $updated_block['settings']['secondary'] = FALSE;
    $manager->applyAction('placeBlock', 'block.block.config_action_test', $updated_block);
    $placed_blocks = \Drupal::entityTypeManager()->getStorage('block')->loadByProperties([
      'id' => 'config_action_test',
    ]);
    $this->assertCount(1, $placed_blocks, 'There is 1 matching block entity');
    $block = array_pop($placed_blocks);
    $block_visibility = $block->getVisibility();
    $this->assertSame('<front>', $block_visibility['request_path']['pages'], 'Block has the modified visibility');
    $block_settings = $block->get('settings');
    $this->assertTrue($block_settings['primary'], 'Block has modified settings');
  }

}
