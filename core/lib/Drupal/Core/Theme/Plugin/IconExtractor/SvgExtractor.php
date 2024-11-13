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
 * This extractor needs the file content, only local SVG are allowed to avoid
 * any security risk. For remote sources, `path` extractor must be used or
 * `svg_sprite` for remote sprite.
 *
 * @internal
 *   This API is experimental.
 */
#[IconExtractor(
  id: 'svg',
  label: new TranslatableMarkup('SVG'),
  description: new TranslatableMarkup('Handles SVG files from one or many paths, remote is not allowed and will be ignored.'),
  forms: [
    'settings' => IconPackExtractorForm::class,
  ]
)]
class SvgExtractor extends IconExtractorWithFinder {

  /**
   * {@inheritdoc}
   */
  public function discoverIcons(): array {
    // Check is included in getFilesFromSources(), but we need to disallow
    // remote sources before.
    $this->checkRequiredConfigSources();
    foreach ($this->configuration['config']['sources'] as $key => $source) {
      if (!empty(parse_url($source, PHP_URL_SCHEME))) {
        unset($this->configuration['config']['sources'][$key]);
      }
    }

    if (empty($this->configuration['config']['sources'])) {
      return [];
    }

    $files = $this->getFilesFromSources();

    if (empty($files)) {
      return [];
    }

    $icons = [];
    foreach ($files as $file) {
      if (!$content = $this->extractSvg($file['absolute_path'] ?? '')) {
        continue;
      }
      $icons[] = $this->createIcon(
        $file['icon_id'],
        $file['source'],
        $file['group'] ?? NULL,
        [
          'content' => $content,
        ],
      );
    }

    return $icons;
  }

  /**
   * Extract svg values, simply exclude parent <svg>.
   *
   * @param string $source
   *   Local path or url to the svg file.
   *
   * @return string|null
   *   The inner SVG content as string.
   *
   * @todo allow some pattern for xpath to select children?
   */
  private function extractSvg(string $source): ?string {
    if (!$content = $this->iconFinder->getFileContents($source)) {
      return NULL;
    }

    libxml_use_internal_errors(TRUE);

    if (!$svg = simplexml_load_string((string) $content)) {
      // @todo do we need to log a warning with the xml error?
      return NULL;
    }

    $content = '';
    foreach ($svg as $child) {
      $content .= $child->asXML();
    }

    return $content;
  }

}
