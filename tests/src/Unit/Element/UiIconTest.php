<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons\Unit\Element;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\ui_icons\Element\UiIcon;
use Drupal\ui_icons\IconDefinition;

/**
 * Tests UiIcon RenderElement class.
 *
 * @group ui_icons
 */
class UiIconTest extends UnitTestCase {

  /**
   * The container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);
  }

  /**
   * Test the getInfo method.
   */
  public function testGetInfo(): void {
    $uiIcon = new UiIcon([], 'test', 'test');
    $info = $uiIcon->getInfo();

    $this->assertArrayHasKey('#pre_render', $info);
    $this->assertArrayHasKey('#iconset', $info);
    $this->assertArrayHasKey('#icon', $info);
    $this->assertArrayHasKey('#options', $info);

    $this->assertSame([['Drupal\ui_icons\Element\UiIcon', 'preRenderUiIcon']], $info['#pre_render']);
    $this->assertSame([], $info['#options']);
  }

  /**
   * @param array $data
   *   The icon data.
   * @param array $expected
   *   The result expected.
   *
   * @dataProvider providerPreRenderUiIcon
   */
  public function testPreRenderUiIcon(array $data, array $expected): void {
    $icon = IconDefinition::create(
      $data['icon_id'],
      $data['icon_source'],
      [
        'iconset_id' => $data['iconset_id'],
        'iconset_label' => $data['iconset_label'] ?? '',
        'template' => $data['icon_template'] ?? '',
        'library' => $data['icon_library'] ?? '',
        'content' => $data['icon_content'] ?? '',
      ],
      $data['icon_group'] ?? NULL,
    );

    $prophecy = $this->prophesize('\Drupal\ui_icons\Plugin\UiIconsetManagerInterface');
    $prophecy->getIcon($data['iconset_id'] . ':' . $data['icon_id'])
      ->willReturn($icon);

    $pluginManagerUiIconset = $prophecy->reveal();
    $this->container->set('plugin.manager.ui_iconset', $pluginManagerUiIconset);

    $element = [
      '#type' => 'ui_icon',
      '#iconset' => $data['iconset_id'],
      '#icon' => $data['icon_id'],
      '#options' => $data['ui_icon_options'] ?? [],
    ];

    $actual = UiIcon::preRenderUiIcon($element);

    $this->assertEquals($expected, $actual['inline-template']);
  }

  /**
   * Test the preRenderUiIcon method.
   */
  public function testPreRenderUiIconNoIcon(): void {
    $prophecy = $this->prophesize('Drupal\ui_icons\Plugin\UiIconsetManagerInterface');
    $prophecy->getIcon('foo:bar')->willReturn(NULL);

    $pluginManagerUiIconset = $prophecy->reveal();
    $this->container->set('plugin.manager.ui_iconset', $pluginManagerUiIconset);

    $element = [
      '#type' => 'ui_icon',
      '#iconset' => 'foo',
      '#icon' => 'bar',
    ];

    $actual = UiIcon::preRenderUiIcon($element);

    $this->assertEquals($element, $actual);
  }

  /**
   * Provides data for testGetFilesFromSource.
   *
   * @return array
   *   Provide test data as:
   *   - array of information for the icon
   *   - result array of render element
   */
  public static function providerPreRenderUiIcon(): array {
    return [
      // Minimum icon definition.
      [
        [
          'iconset_id' => 'iconset_id',
          'icon_id' => 'icon_id',
          'icon_source' => '/foo/bar',
          'icon_template' => 'my_template',
        ],
        [
          '#type' => 'inline_template',
          '#template' => 'my_template',
          '#context' => [
            'icon_id' => 'icon_id',
            'source' => '/foo/bar',
            'content' => '',
            'iconset_label' => '',
          ],
        ],
      ],
      // Full icon definition.
      [
        [
          'iconset_id' => 'iconset_id',
          'iconset_label' => 'Baz',
          'icon_id' => 'icon_id',
          'icon_source' => '/foo/bar',
          'icon_group' => 'test_group',
          'icon_content' => 'test_content',
          'icon_template' => 'my_template',
          'icon_library' => 'my_theme/my_library',
          'ui_icon_options' => ['foo' => 'bar'],
        ],
        [
          '#type' => 'inline_template',
          '#template' => 'my_template',
          '#attached' => ['library' => ['my_theme/my_library']],
          '#context' => [
            'icon_id' => 'icon_id',
            'source' => '/foo/bar',
            'content' => 'test_content',
            'foo' => 'bar',
            'iconset_label' => 'Baz',
          ],
        ],
      ],
    ];
  }

}
