<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons\Unit;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\ui_icons\IconDefinition;
use Drupal\ui_icons\IconDefinitionInterface;

/**
 * Tests IconUnitTestCase Controller class.
 */
abstract class IconUnitTestCase extends UnitTestCase {

  /**
   * Creates icon data result array.
   *
   * @param string|null $icon_pack_id
   *   The ID of the icon set.
   * @param string|null $icon_id
   *   The ID of the icon.
   * @param string|null $icon_pack_label
   *   The label of the icon set.
   *
   * @return array
   *   The icon data array.
   */
  protected static function createIconResultData(?string $icon_pack_id = NULL, ?string $icon_id = NULL, ?string $icon_pack_label = NULL): array {
    return [
      'value' => ($icon_pack_id ?? 'foo') . ':' . ($icon_id ?? 'bar'),
      'label' => new FormattableMarkup('<span class="ui-menu-icon">@icon</span> @name', [
        '@icon' => '_rendered_',
        '@name' => ($icon_id ?? 'bar') . ' (' . ($icon_pack_label ?? 'Baz') . ')',
      ]),
    ];
  }

  /**
   * Creates icon data array.
   *
   * @param string|null $icon_pack_id
   *   The ID of the icon set.
   * @param string|null $icon_id
   *   The ID of the icon.
   * @param string|null $icon_pack_label
   *   The label of the icon set.
   *
   * @return array
   *   The icon data array.
   */
  protected static function createIconData(?string $icon_pack_id = NULL, ?string $icon_id = NULL, ?string $icon_pack_label = NULL): array {
    return [
      ($icon_pack_id ?? 'foo') . ':' . ($icon_id ?? 'bar') => [
        'icon_id' => $icon_id ?? 'bar',
        'source' => 'qux/corge',
        'icon_pack_id' => $icon_pack_id ?? 'foo',
        'icon_pack_label' => $icon_pack_label ?? 'Baz',
      ],
    ];
  }

  /**
   * Create a mock icon.
   *
   * @param array|null $iconData
   *   The icon data to create.
   *
   * @return \Drupal\ui_icons\IconDefinitionInterface
   *   The icon mocked.
   */
  protected function createMockIcon(?array $iconData = NULL): IconDefinitionInterface {
    if (NULL === $iconData) {
      $iconData = [
        'icon_pack_id' => 'foo',
        'icon_id' => 'bar',
      ];
    }

    $icon = $this->prophesize(IconDefinitionInterface::class);
    $icon
      ->getRenderable(['width' => $iconData['width'] ?? '', 'height' => $iconData['height'] ?? ''])
      ->willReturn(['#markup' => '<svg></svg>']);

    $id = $iconData['icon_pack_id'] . ':' . $iconData['icon_id'];
    $icon
      ->getId()
      ->willReturn($id);

    return $icon->reveal();
  }

  /**
   * Create an icon.
   *
   * @param array $iconData
   *   The icon data to create.
   *
   * @return \Drupal\ui_icons\IconDefinitionInterface
   *   The icon mocked.
   */
  protected function createIcon(array $iconData): IconDefinitionInterface {
    return IconDefinition::create(
      $iconData['icon_id'],
      $iconData['source'],
      [
        'icon_pack_id' => $iconData['icon_pack_id'],
        'icon_pack_label' => $iconData['icon_pack_label'],
      ]
    );
  }

}
