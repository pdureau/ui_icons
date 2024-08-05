<?php

declare(strict_types=1);

namespace Drupal\ui_icons\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElementBase;

/**
 * Provides a render element to display an ui icon.
 *
 * Properties:
 * - #iconset: Iconset provider plugin id.
 * - #icon: Name of the icon.
 * - #options: Optional, values sent to the inline Twig template.
 *
 * Usage Example:
 * @code
 * $build['ui_icon'] = [
 *   '#type' => 'ui_icon',
 *   '#iconset' => 'material_symbols',
 *   '#icon' => 'home',
 *   '#options' => [],
 * ];
 * @endcode
 */
#[RenderElement('ui_icon')]
class UiIcon extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#pre_render' => [
        [self::class, 'preRenderUiIcon'],
      ],
      '#iconset' => '',
      '#icon' => '',
      '#options' => [],
    ];
  }

  /**
   * Ui icon element pre render callback.
   *
   * @param array $element
   *   An associative array containing the properties of the ui_icon element.
   *
   * @return array
   *   The modified element.
   */
  public static function preRenderUiIcon(array $element): array {
    /** @var \Drupal\ui_icons\Plugin\UiIconsetManagerInterface $pluginManagerUiIconset */
    $pluginManagerUiIconset = \Drupal::service('plugin.manager.ui_iconset');

    $icon = $pluginManagerUiIconset->getIcon($element['#iconset'] . ':' . $element['#icon']);
    if (!$icon) {
      return $element;
    }
    $element['inline-template'] = $icon->getRenderable($element['#options'] ?? []);

    return $element;
  }

}
