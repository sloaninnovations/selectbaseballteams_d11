<?php

declare(strict_types=1);

namespace Drupal\Core\Template;

use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for icon.
 *
 * @internal
 */
final class IconsTwigExtension extends AbstractExtension {

  /**
   * Creates TwigExtension.
   *
   * @param \Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface $pluginManagerIconPack
   *   The icon plugin manager.
   */
  public function __construct(
    private readonly IconPackManagerInterface $pluginManagerIconPack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('icon', [$this, 'getIconRenderable']),
    ];
  }

  /**
   * Get an icon renderable array.
   *
   * @param string $pack_id
   *   The icon set ID.
   * @param string $icon_id
   *   The icon ID.
   * @param array $settings
   *   An array of settings for the icon.
   *
   * @return array
   *   The icon renderable.
   */
  public function getIconRenderable(?string $pack_id, ?string $icon_id, ?array $settings = []): array {
    if (!$pack_id || !$icon_id) {
      return [];
    }
    $icon_full_id = IconDefinition::createIconId($pack_id, $icon_id);
    $icon = $this->pluginManagerIconPack->getIcon($icon_full_id);
    if (!$icon) {
      return [];
    }

    return $icon->getRenderable($settings ?? []);
  }

}
