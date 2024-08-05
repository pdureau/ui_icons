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
  id: 'path',
  label: new TranslatableMarkup('Local path'),
  description: new TranslatableMarkup('All files from one or many paths. Works for any file type.'),
  forms: [
    'settings' => UiIconsetExtractorForm::class,
  ]
)]
class PathExtractor extends UiIconsExtractorPluginBase {

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
      $icon_full_id = $this->configuration['iconset_id'] . ':' . $file['icon_id'];
      $icons[$icon_full_id] = $this->createIcon($file['name'], $file['relative_path'], $this->configuration, $file['group']);
    }
    return $icons;
  }

}
