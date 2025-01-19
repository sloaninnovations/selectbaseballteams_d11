<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin;

use Composer\Autoload\ClassLoader;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\KernelTests\KernelTestBase;
use Drupal\plugin_test\Plugin\Annotation\PluginExample as AnnotationPluginExample;
use Drupal\plugin_test\Plugin\Attribute\PluginExample as AttributePluginExample;
use org\bovigo\vfs\vfsStream;

/**
 * Tests the default plugin manager.
 *
 * @group Plugin
 */
class DefaultPluginManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['plugin_test'];

  /**
   * Tests annotations and attributes on the default plugin manager.
   */
  public function testDefaultPluginManager(): void {
    $subdir = 'Plugin/plugin_test/custom_annotation';
    $base_directory = $this->root . '/core/modules/system/tests/modules/plugin_test/src';
    $namespaces = new \ArrayObject(['Drupal\plugin_test' => $base_directory]);
    $module_handler = $this->container->get('module_handler');

    // Ensure broken files exist as expected.
    try {
      $e = NULL;
      new \ReflectionClass('\Drupal\plugin_test\Plugin\plugin_test\custom_annotation\ExtendingNonInstalledClass');
    }
    catch (\Throwable $e) {
    } finally {
      $this->assertInstanceOf(\Throwable::class, $e);
      $this->assertSame('Class "Drupal\non_installed_module\NonExisting" not found', $e->getMessage());
    }
    // Ensure there is a class with the expected name. We cannot reflect on this
    // as it triggers a fatal error.
    $this->assertFileExists($base_directory . '/' . $subdir . '/UsingNonInstalledTraitClass.php');

    // Annotation only.
    $manager = new DefaultPluginManager($subdir, $namespaces, $module_handler, NULL, AnnotationPluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayHasKey('example_1', $definitions);
    $this->assertArrayHasKey('example_2', $definitions);
    $this->assertArrayNotHasKey('example_3', $definitions);
    $this->assertArrayNotHasKey('example_4', $definitions);
    $this->assertArrayNotHasKey('example_5', $definitions);

    // Annotations and attributes together.
    $manager = new DefaultPluginManager($subdir, $namespaces, $module_handler, NULL, AttributePluginExample::class, AnnotationPluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayHasKey('example_1', $definitions);
    $this->assertArrayHasKey('example_2', $definitions);
    $this->assertArrayHasKey('example_3', $definitions);
    $this->assertArrayHasKey('example_4', $definitions);
    $this->assertArrayHasKey('example_5', $definitions);

    // Attributes only.
    // \Drupal\Component\Plugin\Discovery\AttributeClassDiscovery does not
    // support parsing classes that cannot be reflected. Therefore, we use VFS
    // to create a directory remove plugin_test's plugins and remove the broken
    // plugins.
    vfsStream::setup('plugin_test');
    $dir = vfsStream::create(['src' => ['Plugin' => ['plugin_test' => ['custom_annotation' => []]]]]);
    $plugin_directory = $dir->getChild('src/' . $subdir);
    vfsStream::copyFromFileSystem($base_directory . '/' . $subdir, $plugin_directory);
    $plugin_directory->removeChild('ExtendingNonInstalledClass.php');
    $plugin_directory->removeChild('UsingNonInstalledTraitClass.php');

    $namespaces = new \ArrayObject(['Drupal\plugin_test' => vfsStream::url('plugin_test/src')]);
    $manager = new DefaultPluginManager($subdir, $namespaces, $module_handler, NULL, AttributePluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayNotHasKey('example_1', $definitions);
    $this->assertArrayNotHasKey('example_2', $definitions);
    $this->assertArrayHasKey('example_3', $definitions);
    $this->assertArrayHasKey('example_4', $definitions);
    $this->assertArrayHasKey('example_5', $definitions);
    $this->assertArrayNotHasKey('extending_non_installed_class', $definitions);
    $this->assertArrayNotHasKey('using_non_installed_trait', $definitions);
  }

  /**
   * Tests plugin property attributes on the default plugin manager.
   */
  public function testDefaultPluginManagerWithPluginProperties(): void {
    $subdir = 'Plugin/plugin_test/plugin_property';
    $base_directory = $this->root . '/core/modules/system/tests/modules/plugin_test/src';
    $namespaces = new \ArrayObject(['Drupal\plugin_test' => $base_directory]);

    // When plugin_test_extended is not installed, only plugin properties
    // without module dependencies are set.
    $manager = new DefaultPluginManager($subdir, $namespaces, $this->container->get('module_handler'), NULL, AttributePluginExample::class, AnnotationPluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayHasKey('example_with_plugin_property', $definitions);
    $this->assertArrayHasKey('core_plugin_property', $definitions['example_with_plugin_property']);
    $this->assertEquals('core plugin property value', $definitions['example_with_plugin_property']['core_plugin_property']);
    $this->assertArrayNotHasKey('plugin_test_extended_plugin_property', $definitions['example_with_plugin_property']);

    $this->assertArrayHasKey('example_object_definition', $definitions);
    $this->assertTrue(isset($definitions['example_object_definition']->core_plugin_property_with_callback));
    $this->assertEquals('core plugin property with callback value', $definitions['example_object_definition']->core_plugin_property_with_callback);
    $this->assertTrue(isset($definitions['example_object_definition']->nested));
    $this->assertEquals(['key' => 'nested key'], $definitions['example_object_definition']->nested);

    // Install plugin_test_extended and now plugin property from attribute with
    // plugin_test_extended module dependency is set.
    $this->container->get('module_installer')->install(['plugin_test_extended']);
    // Container has new instance of module handler after module install, so
    // need to reset.
    $manager = new DefaultPluginManager($subdir, $namespaces, $this->container->get('module_handler'), NULL, AttributePluginExample::class, AnnotationPluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayHasKey('example_with_plugin_property', $definitions);
    $this->assertArrayHasKey('core_plugin_property', $definitions['example_with_plugin_property']);
    $this->assertEquals('core plugin property value', $definitions['example_with_plugin_property']['core_plugin_property']);
    $this->assertArrayHasKey('plugin_test_extended_plugin_property', $definitions['example_with_plugin_property']);
    $this->assertEquals('plugin_test_extended plugin property value', $definitions['example_with_plugin_property']['plugin_test_extended_plugin_property']);

    // Uninstall plugin_test_extended and now only plugin properties without
    // plugin_test_extended module dependencies are set.
    $this->container->get('module_installer')->uninstall(['plugin_test_extended']);
    // Container has new instance of module handler after module uninstall, so
    // need to reset.
    $manager = new DefaultPluginManager($subdir, $namespaces, $this->container->get('module_handler'), NULL, AttributePluginExample::class, AnnotationPluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayHasKey('example_with_plugin_property', $definitions);
    $this->assertArrayHasKey('core_plugin_property', $definitions['example_with_plugin_property']);
    $this->assertEquals('core plugin property value', $definitions['example_with_plugin_property']['core_plugin_property']);
    $this->assertArrayNotHasKey('plugin_test_extended_plugin_property', $definitions['example_with_plugin_property']);

    // Use VFS to create a directory to test broken plugin definitions.
    vfsStream::setup('plugin_test');
    $dir = vfsStream::create(['src' => ['Plugin' => ['plugin_test' => ['plugin_property' => []]]]]);
    $namespaces = new \ArrayObject(['Drupal\plugin_test' => vfsStream::url('plugin_test/src')]);
    $class_loader = new ClassLoader();
    $class_loader->addPsr4("Drupal\\plugin_test\\", vfsStream::url("plugin_test/src"));
    $class_loader->register(TRUE);
    $plugin_directory = $dir->getChild('src/' . $subdir);
    vfsStream::copyFromFileSystem($base_directory . '/' . $subdir, $plugin_directory);

    // Test plugin property ::isValidPluginClass().
    $invalid_plugin_class = <<<'EOS'
<?php
declare(strict_types=1);
namespace Drupal\plugin_test\Plugin\plugin_test\plugin_property;
use Drupal\Core\Entity\Attribute\EntityTypeProperty;
use Drupal\plugin_test\Plugin\Attribute\PluginExample;
#[PluginExample(
  id: 'invalid_plugin_class',
  custom: 'Example with invalid plugin class for EntityTypeProperty',
)]
#[EntityTypeProperty(
  key: 'invalid',
  value: 'invalid',
)]
class InvalidPluginClass {}
EOS;
    $file = vfsStream::newFile('InvalidPluginClass.php')->withContent($invalid_plugin_class);
    $plugin_directory->addChild($file);
    $manager = new DefaultPluginManager($subdir, $namespaces, $this->container->get('module_handler'), NULL, AttributePluginExample::class, AnnotationPluginExample::class);
    try {
      $e = NULL;
      $definitions = $manager->getDefinitions();
    }
    catch (InvalidPluginDefinitionException $e) {
    }
    $this->assertInstanceOf(InvalidPluginDefinitionException::class, $e);
    $this->assertSame('May not use plugin property class Drupal\Core\Entity\Attribute\EntityTypeProperty with main plugin attribute class "Drupal\plugin_test\Plugin\Attribute\PluginExample for plugin class Drupal\plugin_test\Plugin\plugin_test\plugin_property\InvalidPluginClass".', $e->getMessage());
    $this->assertArrayNotHasKey('invalid_plugin_class', $definitions);

    // Test plugin property with invalid $addToDefinitionCallback.
    $plugin_directory->removeChild('InvalidPluginClass.php');
    $invalid_callback = <<<'EOS'
<?php
declare(strict_types=1);
namespace Drupal\plugin_test\Plugin\plugin_test\plugin_property;
use Drupal\Core\Plugin\Attribute\PluginProperty;
use Drupal\plugin_test\Plugin\Attribute\PluginExample;
#[PluginExample(
  id: 'invalid_callback',
  custom: 'Invalid example with plugin property invalid addToDefinitionCallback',
)]
#[PluginProperty(
  key: 1,
  value: 0,
  addToDefinitionCallback: ['\NonExistentClass', 'nonExistentMethod'],
)]
class InvalidCallback {}
EOS;
    $file = vfsStream::newFile('InvalidCallback.php')->withContent($invalid_callback);
    $plugin_directory->addChild($file);
    $manager = new DefaultPluginManager($subdir, $namespaces, $this->container->get('module_handler'), NULL, AttributePluginExample::class, AnnotationPluginExample::class);
    $manager->clearCachedDefinitions();
    try {
      $e = NULL;
      $definitions = $manager->getDefinitions();
    }
    catch (InvalidPluginDefinitionException $e) {
    }
    $this->assertInstanceOf(InvalidPluginDefinitionException::class, $e);
    $this->assertSame('Can not add property to plugin definition because specified addToDefinitionCallback is invalid.', $e->getMessage());
    $this->assertArrayNotHasKey('invalid_callback', $definitions);

    // Test plugin property in a module is invalid.
    $plugin_directory->removeChild('InvalidCallback.php');
    $invalid_callback = <<<'EOS'
<?php
declare(strict_types=1);
namespace Drupal\plugin_test\Plugin\plugin_test\plugin_property;
use Drupal\plugin_test\Plugin\Attribute\PluginExample;
use Drupal\plugin_test_extended\Plugin\Attribute\InvalidPluginProperty;
#[PluginExample(
  id: 'invalid_module_plugin_property',
  custom: 'Invalid example with plugin property in module',
)]
#[InvalidPluginProperty(
  key: 1,
  value: 0,
)]
class InvalidModulePropertyExamplePlugin {}
EOS;
    $file = vfsStream::newFile('InvalidModulePropertyExamplePlugin.php')->withContent($invalid_callback);
    $plugin_directory->addChild($file);
    $manager = new DefaultPluginManager($subdir, $namespaces, $this->container->get('module_handler'), NULL, AttributePluginExample::class, AnnotationPluginExample::class);
    $manager->clearCachedDefinitions();
    try {
      $e = NULL;
      $definitions = $manager->getDefinitions();
    }
    catch (InvalidPluginDefinitionException $e) {
    }
    $this->assertInstanceOf(InvalidPluginDefinitionException::class, $e);
    $this->assertSame('Invalid plugin property class: Drupal\plugin_test_extended\Plugin\Attribute\InvalidPluginProperty used in plugin class Drupal\plugin_test\Plugin\plugin_test\plugin_property\InvalidModulePropertyExamplePlugin. Plugin property classes can be implemented only in core or the module providing the plugin type.', $e->getMessage());
    $this->assertArrayNotHasKey('invalid_module_plugin_property', $definitions);
  }

}
