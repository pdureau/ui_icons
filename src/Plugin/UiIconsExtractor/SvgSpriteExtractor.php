<?php

declare(strict_types=1);

namespace Drupal\ui_icons\Plugin\UiIconsExtractor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_icons\Attribute\UiIconsExtractor;
use Drupal\ui_icons\Exception\IconsetConfigErrorException;
use Drupal\ui_icons\Plugin\UiIconsExtractorPluginBase;
use Drupal\ui_icons\PluginForm\UiIconsetExtractorForm;

/**
 * Plugin implementation of the ui_icons_extractor.
 */
#[UiIconsExtractor(
  id: 'svg_sprite',
  label: new TranslatableMarkup('SVG Sprite'),
  description: new TranslatableMarkup('Open an SVG XML file and get the icons.'),
  forms: [
    'settings' => UiIconsetExtractorForm::class,
  ]
)]
class SvgSpriteExtractor extends UiIconsExtractorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getIcons(): array {
    if (!isset($this->configuration['config']['sources'])) {
      throw new IconsetConfigErrorException(sprintf('Missing `config: sources` in your definition, extractor %s require this value.', $this->getPluginId()));
    }

    $files = $this->getFilesFromSources($this->configuration['config']['sources'] ?? [], $this->configuration['_path_info'] ?? []);

    if (empty($files)) {
      return [];
    }

    $icons = [];
    foreach ($files as $file) {
      $icon_ids = $this->extractSvgSymbolId($file['absolute_path']);
      foreach ($icon_ids as $icon_id) {
        $icon_full_id = $this->configuration['iconset_id'] . ':' . $icon_id;
        $icons[$icon_full_id] = $this->createIcon($icon_id, $file['relative_path'], $this->configuration, $file['group']);
      }
    }

    return $icons;
  }

  /**
   * Extract svg values.
   *
   * @param string $uri
   *   Local path to the svg file.
   *
   * @return array
   *   The list of id from all <symbol>.
   */
  private function extractSvgSymbolId(string $uri): array {
    $content = $this->uiIconsFinder->getFileContents($uri);

    libxml_use_internal_errors(TRUE);
    $svg = simplexml_load_string($content);
    if ($svg === FALSE) {
      $errors = [];
      foreach (libxml_get_errors() as $error) {
        $errors[] = trim($error->message);
      }
      return $errors;
    }

    $ids = [];
    foreach ($svg->symbol as $symbol) {
      if (isset($symbol['id'])) {
        $ids[] = (string) $symbol['id'];
      }
    }

    return $ids;
  }

}
