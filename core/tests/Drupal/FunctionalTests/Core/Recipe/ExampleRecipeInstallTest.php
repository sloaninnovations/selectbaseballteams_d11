<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Core\Recipe;

use Drupal\FunctionalTests\Installer\InstallerTestBase;
// use Drupal\Tests\standard\Traits\StandardTestTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

/**
 * Tests installing the Example recipe via the installer.
 *
 * @group #slow
 * @group Recipe
 */
class ExampleRecipeInstallTest extends InstallerTestBase {
  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = '';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Skip permissions hardening so we can write a services file later.
    $this->settings['settings']['skip_permissions_hardening'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function visitInstaller(): void {
    // Use a URL to install from a recipe.
    $this->drupalGet($GLOBALS['base_url'] . '/core/install.php' . '?profile=&recipe=core/recipes/example');
  }

  /**
   * {@inheritdoc}
   */
  public function testExample(): void {
    // Check if the value for the config is the same we set in the Example recipe.
    $store_text_settings = $this->config('text.settings')->get('default_summary_length');
    $this->assertSame(700, $store_text_settings, 'The default summary length is 700');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpProfile(): void {
    // Noop. This form is skipped due the parameters set on the URL.
  }

  protected function installDefaultThemeFromClassProperty(ContainerInterface $container): void {
    // In this context a default theme makes no sense.
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite(): void {
    $services_file = DRUPAL_ROOT . '/' . $this->siteDirectory . '/services.yml';
    // $content = file_get_contents($services_file);

    // Disable the super user access.
    $yaml = new SymfonyYaml();
    $services = [];
    $services['parameters']['security.enable_super_user'] = FALSE;
    file_put_contents($services_file, $yaml->dump($services));
    parent::setUpSite();
  }

}
