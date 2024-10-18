<?php

declare(strict_types=1);

namespace Drupal\ui_icons_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\ui_patterns\Attribute\Source;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'icon_renderable',
  label: new TranslatableMarkup('Icon'),
  description: new TranslatableMarkup('Render an icon from UI Icons module.'),
  prop_types: ['slot']
)]
class IconRenderableSource extends IconSource {

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $value = $this->getSetting('value');
    if (!$value) {
      return [];
    }
    [$pack_id, $icon_id] = explode(IconDefinition::ICON_SEPARATOR, $value['icon_id']);
    return [
      '#type' => 'icon',
      '#pack_id' => $pack_id ?: '',
      '#icon_id' => $icon_id ?: '',
      '#settings' => $value['icon_settings'][$pack_id] ?? [],
    ];
  }

}
