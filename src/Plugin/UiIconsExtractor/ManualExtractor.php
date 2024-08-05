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
  id: 'manual',
  label: new TranslatableMarkup('Manual'),
  description: new TranslatableMarkup('Put the list of icons directly in the config.'),
  forms: [
    'settings' => UiIconsetExtractorForm::class,
  ]
)]
class ManualExtractor extends UiIconsExtractorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getIcons(): array {
    if (!isset($this->configuration['config']['icons'])) {
      throw new IconsetConfigErrorException(sprintf('Missing `config: icons` in your definition, extractor %s require this value.', $this->getPluginId()));
    }

    $icons = [];
    foreach ($this->configuration['config']['icons'] as $icon) {
      if (!is_array($icon)) {
        continue;
      }

      $icon_full_id = $this->configuration['iconset_id'] . ':' . $icon['name'];
      // @todo relative path?
      $icons[$icon_full_id] = $this->createIcon($icon['name'], $icon['source'], $this->configuration, $icon['group'] ?? NULL);
    }

    return $icons;
  }

}
