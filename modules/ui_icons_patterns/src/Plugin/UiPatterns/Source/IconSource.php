<?php

declare(strict_types=1);

namespace Drupal\ui_icons_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\SourcePluginBase;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'icon',
  label: new TranslatableMarkup('Icon'),
  description: new TranslatableMarkup('Get an icon from UI Icons module.'),
  prop_types: ['icon']
)]
class IconSource extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $value = $this->getSetting('value');
    $icon_settings = $value['icon_settings'] ?? [];

    if (!$icon_data = IconDefinition::getIconDataFromId($value['icon_id'] ?? '')) {
      return NULL;
    }

    $icon_data['settings'] = $icon_settings[$icon_data['pack_id']] ?? [];

    return $icon_data;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $value = $this->getSetting('value');
    $element = [
      'value' => [
        '#type' => 'icon_autocomplete',
        '#default_value' => $value['icon_id'] ?? '',
        '#default_settings' => $value['icon_settings'] ?? [],
        '#show_settings' => TRUE,
        '#return_id' => TRUE,
      ],
    ];

    if (isset($this->propDefinition['properties']['pack_id']['enum'])) {
      $icon_packs = $this->propDefinition['properties']['pack_id']['enum'];
      $element['value']['#allowed_icon_pack'] = $icon_packs;
      $element['value']['#description'] = $this->t("Allowed icon packs: @values", ["@values" => implode(',', $icon_packs)]);
    }

    return $element;
  }

}
