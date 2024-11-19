<?php

declare(strict_types=1);

namespace Drupal\ui_icons_backport\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\Icon\IconDefinition;

/**
 * Provides a render element to display an icon.
 *
 * Properties:
 * - #pack_id: (string) Icon Pack provider plugin id.
 * - #icon_id: (string) Name of the icon.
 * - #settings: (array) Settings sent to the inline Twig template.
 *
 * Usage Example:
 * @code
 * $build['icon'] = [
 *   '#type' => 'icon',
 *   '#pack_id' => 'material_symbols',
 *   '#icon_id' => 'home',
 *   '#settings' => [
 *     'width' => 64,
 *   ],
 * ];
 * @endcode
 *
 * @internal
 */
#[RenderElement('icon')]
class Icon extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#pre_render' => [
        [self::class, 'preRenderIcon'],
      ],
      '#pack_id' => '',
      '#icon_id' => '',
      '#settings' => [],
    ];
  }

  /**
   * Icon element pre render callback.
   *
   * @param array $element
   *   An associative array containing the properties of the icon element.
   *
   * @return array
   *   The modified element.
   */
  public static function preRenderIcon(array $element): array {
    $icon_full_id = IconDefinition::createIconId($element['#pack_id'], $element['#icon_id']);

    $iconCollector = \Drupal::service('Drupal\Core\Theme\Icon\IconCollector');
    $icon = $iconCollector->get($icon_full_id);

    if (!$icon) {
      return $element;
    }

    $context = [
      'icon_id' => $icon->getIconId(),
    ];
    if ($source = $icon->getSource()) {
      $context['source'] = $source;
    }

    // Pass all data to the template, extractors can add specific values.
    if ($data = $icon->getData()) {
      if (is_array($data)) {
        foreach ($data as $data_name => $data_value) {
          if (!$data_value) {
            continue;
          }
          $context[$data_name] = $data_value;
        }
      }
    }

    // Inject attributes variable if not created by the extractor.
    if (!isset($context['attributes'])) {
      $context['attributes'] = new Attribute();
    }

    $element['inline-template'] = [
      '#type' => 'inline_template',
      '#template' => $icon->getTemplate(),
      // Settings are added to be always from element and not extractor.
      '#context' => $context + $element['#settings'],
    ];

    if ($library = $icon->getLibrary()) {
      $element['inline-template']['#attached'] = ['library' => [$library]];
    }

    return $element;
  }

}
