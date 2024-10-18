<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons_backport;

use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Core\Theme\Icon\IconDefinitionInterface;

/**
 * Provides methods to generate icons for tests.
 */
trait IconTestTrait {

  /**
   * Create a mock icon.
   *
   * @param array<string, string>|null $iconData
   *   The icon data to create.
   *
   * @return \Drupal\Core\Theme\Icon\IconDefinitionInterface
   *   The icon mocked.
   */
  protected function createMockIcon(?array $iconData = NULL): IconDefinitionInterface {
    if (NULL === $iconData) {
      $iconData = [
        'pack_id' => 'foo',
        'icon_id' => 'bar',
      ];
    }

    $icon = $this->prophesize(IconDefinitionInterface::class);
    $icon
      ->getRenderable(['width' => $iconData['width'] ?? '', 'height' => $iconData['height'] ?? ''])
      ->willReturn(['#markup' => '<svg></svg>']);

    $icon_full_id = IconDefinition::createIconId($iconData['pack_id'], $iconData['icon_id']);
    $icon
      ->getId()
      ->willReturn($icon_full_id);

    return $icon->reveal();
  }

  /**
   * Create an icon.
   *
   * @param array $data
   *   The icon data to create.
   *
   * @return \Drupal\Core\Theme\Icon\IconDefinitionInterface
   *   The icon mocked.
   */
  protected function createTestIcon(array $data = []): IconDefinitionInterface {
    $filtered_data = $data;
    $keys = ['pack_id', 'icon_id', 'template', 'source', 'group'];
    foreach ($keys as $key) {
      unset($filtered_data[$key]);
    }
    return IconDefinition::create(
      $data['pack_id'] ?? 'foo',
      $data['icon_id'] ?? 'bar',
      $data['template'] ?? 'baz',
      $data['source'] ?? NULL,
      $data['group'] ?? NULL,
      $filtered_data,
    );
  }

}
