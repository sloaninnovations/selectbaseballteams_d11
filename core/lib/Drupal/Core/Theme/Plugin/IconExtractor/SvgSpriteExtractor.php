<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Plugin\IconExtractor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\Icon\Attribute\IconExtractor;
use Drupal\Core\Theme\Icon\IconExtractorWithFinder;
use Drupal\Core\Theme\Icon\IconPackExtractorForm;

/**
 * Plugin implementation of the icon_extractor.
 *
 * This extractor need the file content, remote url will raise too much risks
 * and slow down if the file is not available anymore. Then `path` extractor
 * must be used.
 *
 * @internal
 *   This API is experimental.
 */
#[IconExtractor(
  id: 'svg_sprite',
  label: new TranslatableMarkup('SVG Sprite'),
  description: new TranslatableMarkup('Open an SVG XML file and get the icons.'),
  forms: [
    'settings' => IconPackExtractorForm::class,
  ]
)]
class SvgSpriteExtractor extends IconExtractorWithFinder {

  /**
   * {@inheritdoc}
   */
  public function discoverIcons(): array {
    $files = $this->getFilesFromSources();

    if (empty($files)) {
      return [];
    }

    $icons = [];
    foreach ($files as $file) {
      $icon_ids = $this->extractIdsFromXml($file['absolute_path'] ?? '');
      foreach ($icon_ids as $icon_id) {
        $icons[] = $this->createIcon((string) $icon_id, $file['source'], $file['group'] ?? NULL);
      }
    }

    return $icons;
  }

  /**
   * Extract icon ID from XML.
   *
   * @param string $source
   *   Local path or url to the svg file.
   *
   * @return array
   *   A list of icons ID.
   */
  private function extractIdsFromXml(string $source): array {
    if (!$content = $this->iconFinder->getFileContents($source)) {
      return [];
    }

    libxml_use_internal_errors(TRUE);

    if (!$svg = simplexml_load_string((string) $content)) {
      // @todo log a warning with the xml error.
      return [];
    }
    if ($svg->symbol) {
      return $this->extractIdsFromSymbols($svg->symbol);
    }
    if ($svg->defs->symbol) {
      return $this->extractIdsFromSymbols($svg->defs->symbol);
    }

    return [];
  }

  /**
   * Extract icon ID from SVG symbols.
   *
   * @param \SimpleXMLElement $wrapper
   *   A SVG element.
   *
   * @return array
   *   A list of icons ID.
   */
  private function extractIdsFromSymbols(\SimpleXMLElement $wrapper): array {
    $ids = [];
    foreach ($wrapper as $symbol) {
      if (isset($symbol['id'])) {
        $ids[] = (string) $symbol['id'];
      }
    }

    return $ids;
  }

}
