<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\KernelTests\KernelTestBase;
use Drupal\plugin_test\Plugin\Annotation\PluginExample as AnnotationPluginExample;
use Drupal\plugin_test\Plugin\Attribute\PluginExample as AttributePluginExample;

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
    $this->assertArrayNotHasKey("example_with_dependencies_attribute", $definitions);

    // Attributes only.
    $manager = new DefaultPluginManager($subdir, $namespaces, $module_handler, NULL, AttributePluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayNotHasKey('example_1', $definitions);
    $this->assertArrayNotHasKey('example_2', $definitions);
    $this->assertArrayHasKey('example_3', $definitions);
    $this->assertArrayHasKey('example_4', $definitions);
    $this->assertArrayHasKey('example_5', $definitions);
    $this->assertArrayNotHasKey('extending_non_installed_class', $definitions);
    $this->assertArrayNotHasKey('using_non_installed_trait', $definitions);
    $this->assertArrayNotHasKey("example_with_dependencies_attribute", $definitions);

    // Test that example with dependencies is discovered after module dependency
    // is installed.
    $this->container->get('module_installer')->install(['plugin_test_extended']);
    // There is a new module handler instance after module install.
    $module_handler = $this->container->get('module_handler');
    $manager = new DefaultPluginManager($subdir, $namespaces, $module_handler, NULL, AttributePluginExample::class, AnnotationPluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayHasKey("example_with_dependencies_attribute", $definitions);

    // Test that example with dependencies is not discovered after module
    // dependency is back to being uninstalled.
    $this->container->get('module_installer')->uninstall(['plugin_test_extended']);
    // There is a new module handler instance after module uninstall.
    $module_handler = $this->container->get('module_handler');
    $manager = new DefaultPluginManager($subdir, $namespaces, $module_handler, NULL, AttributePluginExample::class, AnnotationPluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayNotHasKey("example_with_dependencies_attribute", $definitions);
  }

}
