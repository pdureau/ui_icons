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
  id: 'svg',
  label: new TranslatableMarkup('SVG'),
  description: new TranslatableMarkup('All files from one or many paths. Works for any file type.'),
  forms: [
    'settings' => UiIconsetExtractorForm::class,
  ]
)]
class SvgExtractor extends UiIconsExtractorPluginBase {

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
      $this->configuration['content'] = $this->extractSvg($file['absolute_path']);
      $icon_full_id = $this->configuration['iconset_id'] . ':' . $file['icon_id'];
      $icons[$icon_full_id] = $this->createIcon($file['name'], $file['relative_path'], $this->configuration, $file['group']);
    }

    return $icons;
  }

  /**
   * Extract svg values, simply exclude parent <svg>.
   *
   * @param string $uri
   *   Local path to the svg file.
   *
   * @return string
   *   The inner SVG content as string.
   *
   * @todo allow some pattern for xpath to select children?
   */
  private function extractSvg(string $uri): string {
    $content = $this->uiIconsFinder->getFileContents($uri);

    libxml_use_internal_errors(TRUE);
    $svg = simplexml_load_string($content);
    if ($svg === FALSE) {
      $errors = [];
      foreach (libxml_get_errors() as $error) {
        $errors[] = $error->message;
      }
      return implode(', ', $errors);
    }

    $content = '';
    foreach ($svg as $child) {
      $content .= $child->asXML();
    }
    return $content;
  }

}