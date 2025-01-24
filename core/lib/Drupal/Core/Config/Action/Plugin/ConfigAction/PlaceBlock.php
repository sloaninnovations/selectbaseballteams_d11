<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action\Plugin\ConfigAction;

use Drupal\block\BlockInterface;
use Drupal\block\Entity\Block;
use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'placeBlock',
  admin_label: new TranslatableMarkup('Place block'),
)]
final class PlaceBlock implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Constructs a PlaceBlock object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The config manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityTypeManager utility class.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly ConfigManagerInterface $configManager,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ThemeHandlerInterface $themeHandler,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.manager'),
      $container->get('entity_type.manager'),
      $container->get('theme_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    if (empty($value)) {
      throw new ConfigActionException(sprintf('Block %s cannot be created because no configuration was provided', $configName));
    }

    // Expect $value to be an array whose keys are the block entity properties.
    if (!is_array($value)) {
      throw new ConfigActionException(sprintf('Block %s cannot be created because provided configuration is not an array', $configName));
    }

    $entity_type_id = $this->configManager->getEntityTypeIdByName($configName);
    if ($entity_type_id !== 'block') {
      throw new ConfigActionException(sprintf("Provided config entity %s is not a block", $configName));
    }

    // A plugin value is required.
    if (empty($value['plugin'])) {
      throw new ConfigActionException(sprintf('Block %s requires a plugin value', $configName));
    }

    if (empty($value['id'])) {
      // First, try to parse a fallback value.
      if (strpos($configName, 'block.block.') === 0) {
        $value['id'] = substr($configName, 12);
      }
      // If still empty, throw an exception.
      if (empty($value['id'])) {
        throw new ConfigActionException(sprintf('Unable to determine a valid id for block %s', $configName));
      }
    }

    $theme_name = $value['theme'] ?? NULL;
    if (!$theme_name) {
      throw new ConfigActionException(sprintf('Block %s cannot be created because of missing theme identifier', $configName));
    }

    // Replace "default" or "admin" placeholders with appropriate config value.
    if (in_array($theme_name, ['default', 'admin'])) {
      $config_theme_name = $this->configFactory->get('system.theme')->get($theme_name);
      assert(is_string($config_theme_name));
      if ($config_theme_name) {
        $value['theme'] = $config_theme_name;
        $value['dependencies']['theme'] = $config_theme_name;
        $value['id'] = str_replace($theme_name, $config_theme_name, $value['id']);
        $configName = str_replace($theme_name, $config_theme_name, $configName);
      }
    }
    // If a named theme is the target, validate that it is installed.
    else {
      $installed_themes = $this->themeHandler->listInfo();
      if (!isset($installed_themes[$theme_name])) {
        throw new ConfigActionException(sprintf('Block %s cannot be created because the specified theme is missing', $configName));
      }
    }

    // Validate that the specified region exist, and use fallback if needed.
    $available_regions = system_region_list($value['theme']);
    $specified_region = $value['region'] ?? '';
    if (is_array($specified_region)) {
      throw new ConfigActionException(sprintf('Region can only have one value. To specify multiple possible regions, use "regions".'));
    }
    $regions = $value['regions'] ?? '';
    if ((empty($specified_region) && empty($regions))) {
      throw new ConfigActionException(sprintf('Block %s cannot be created because a region must be specified', $configName));
    }
    $valid_region = FALSE;
    if (isset($available_regions[$specified_region])) {
      $valid_region = TRUE;
    }
    elseif ($regions) {
      // Normalize $regions to an array.
      foreach ((array) $regions as $target_region) {
        if (isset($available_regions[$target_region])) {
          $value['region'] = $target_region;
          $valid_region = TRUE;
          break;
        }
      }
    }
    if (!$valid_region) {
      throw new ConfigActionException(sprintf('Block %s could not identify a valid region', $configName));
    }

    // Check if the block already exists.
    $block = $this->configManager->loadConfigEntityByName($configName);
    if ($block) {
      $this->updateExistingBlock($configName, $block, $value);
      return;
    }

    // Clean up a potential value not meant for import.
    if (isset($value['regions'])) {
      unset($value['regions']);
    }

    // Set a default array to ensure expected keys are present.
    $defaults = [
      'status' => TRUE,
      'dependencies' => [],
      'weight' => 0,
      'provider' => NULL,
      'settings' => [],
      'visibility' => [],
    ];
    $value = $value + $defaults;

    // Replace weight keywords with appropriate values.
    if (in_array($value['weight'], ['first', 'last'])) {
      $this->setWeight($value);
    }

    // If no id in the settings, populate with the plugin value.
    if (empty($value['settings']['id'])) {
      $value['settings']['id'] = $value['plugin'];
    }

    // Create and save the block entity.
    $block = Block::create($value);
    $block->save();
  }

  /**
   * Programmatically determine the weight for the block.
   *
   * @param array $value
   *   The configuration passed to the config action.
   */
  protected function setWeight(&$value) {
    $region_blocks = $this->entityTypeManager->getStorage('block')->loadByProperties([
      'theme' => $value['theme'],
      'region' => $value['region'],
    ]);
    if ($region_blocks) {
      uasort($region_blocks, 'Drupal\block\Entity\Block::sort');
      if ($value['weight'] === 'first') {
        $ref_block = array_shift($region_blocks);
        $value['weight'] = $ref_block->getWeight() - 1;
      }
      else {
        $ref_block = array_pop($region_blocks);
        $value['weight'] = $ref_block->getWeight() + 1;
      }
    }
    else {
      // No existing blocks, so default to zero.
      $value['weight'] = 0;
    }
  }

  /**
   * Updates an existing block based on the provided values.
   *
   * @param string $configName
   *   The name of the block configuration entity.
   * @param \Drupal\block\BlockInterface $block
   *   The block to update.
   * @param array $value
   *   The configuration passed to the config action.
   */
  protected function updateExistingBlock(string $configName, BlockInterface $block, $value) {
    $block_changed = FALSE;
    if ($block->getTheme() !== $value['theme']) {
      throw new ConfigActionException(sprintf('Unable to place block %s because a block with this name has been placed in a different theme', $configName));
    }
    if ($block->getPluginId() !== $value['plugin']) {
      throw new ConfigActionException(sprintf('Unable to place block %s because a block with this name has been placed but uses a different plugin', $configName));
    }
    // Override the block's region.
    if ($block->getRegion() !== $value['region']) {
      $block->setRegion($value['region']);
      $block_changed = TRUE;
    }
    // Override the block's visibility.
    if (isset($value['visibility']) && is_array($value['visibility'])) {
      foreach ($value['visibility'] as $config_id => $visibility) {
        $block->setVisibilityConfig($config_id, $visibility);
      }
      $block_changed = TRUE;
    }
    // Override the block's settings with anything from the action values.
    $value_settings = $value['settings'] ?? [];
    if ($value_settings) {
      $block_settings = $block->get('settings');
      foreach ($value_settings as $key => $setting) {
        $block_settings[$key] = $setting;
      }
      $block->set('settings', $block_settings);
      $block_changed = TRUE;
    }
    if ($block_changed) {
      $block->save();
    }
  }

}
