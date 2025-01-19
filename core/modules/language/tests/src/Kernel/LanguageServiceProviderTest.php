<?php

namespace Drupal\Tests\language\Kernel;

use Drupal\Core\Config\BootstrapConfigStorageFactory;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\ConfigurableLanguageManager;
use Drupal\language\EventSubscriber\LanguageRequestSubscriber;
use Drupal\language\HttpKernel\PathProcessorLanguage;
use Drupal\Tests\language\Traits\LanguageTestTrait;

/**
 * @coversDefaultClass \Drupal\language\LanguageServiceProvider
 * @group language
 */
class LanguageServiceProviderTest extends KernelTestBase {

  use LanguageTestTrait;

  protected static $modules = ['language'];

  /**
   * @covers ::register
   * @covers ::isMultilingual
   */
  public function testRegisterDefault() {
    $this->assertFalse($this->container->has('language_request_subscriber'), 'Language request subscriber not registered');
    $this->assertFalse($this->container->has('path_processor_language'), 'Language path processor not registered');
  }

  /**
   * @covers ::register
   * @covers ::isMultilingual
   */
  public function testRegisterMultilingual() {
    $this->setupMultilingual();

    $container = $this->container;
    $this->assertTrue($container->has('language_request_subscriber'), 'Language request subscriber not registered');
    $this->assertTrue($container->has('path_processor_language'), 'Language path processor not registered');
    $this->assertInstanceOf(LanguageRequestSubscriber::class, $this->container->get('language_request_subscriber'));
    $this->assertInstanceOf(PathProcessorLanguage::class, $this->container->get('path_processor_language'));
  }

  /**
   * @covers ::alter
   * @covers ::isMultilingual
   */
  public function testAlterDefault() {
    $this->assertInstanceOf(LanguageManager::class, $this->container->get('language_manager'));
    $this->assertEquals(Language::$defaultValues, $this->container->getParameter('language.default_values'));
    $this->assertContains('languages:' . LanguageInterface::TYPE_INTERFACE, $this->container->getParameter('renderer.config')['required_cache_contexts']);
    $this->assertNotContains('languages:' . LanguageInterface::TYPE_CONTENT, $this->container->getParameter('renderer.config')['required_cache_contexts']);
  }

  /**
   * @covers ::alter
   * @covers ::isMultilingual
   */
  public function testAlterMultilingual() {
    $this->setupMultilingual();

    $container = $this->container;
    $this->assertInstanceOf(ConfigurableLanguageManager::class, $container->get('language_manager'));

    // TODO Test modified default language.

    // Assert language is part of required contexts.
    $this->assertContains('languages:' . LanguageInterface::TYPE_INTERFACE, $container->getParameter('renderer.config')['required_cache_contexts']);
    $this->assertContains('languages:' . LanguageInterface::TYPE_CONTENT, $container->getParameter('renderer.config')['required_cache_contexts']);
  }

  /**
   * Sets up required multilingual config for testing a multilingual site.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setupMultilingual() {
    // Create the translation language.
    $this->installConfig(['language']);
    self::createLanguageFromLangcode('de');

    $config = $this->config('language.types');
    $config->set('configurable', [
      LanguageInterface::TYPE_INTERFACE,
      LanguageInterface::TYPE_CONTENT,
    ]);
    // Hack this directly into bootstrap config.
    BootstrapConfigStorageFactory::get()->write('language.types', [
      'configurable' => [
        LanguageInterface::TYPE_INTERFACE,
        LanguageInterface::TYPE_CONTENT,
      ],
    ]);

    // Trigger a container rebuild so we can test that register worked correctly.
    $this->container->get('kernel')->rebuildContainer();
  }

}
