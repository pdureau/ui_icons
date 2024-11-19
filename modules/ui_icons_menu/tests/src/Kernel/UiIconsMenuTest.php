<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons_menu\Kernel;

@class_alias('Drupal\ui_icons_backport\IconDefinitionInterface', 'Drupal\Core\Theme\Icon\IconDefinitionInterface');
@class_alias('Drupal\ui_icons_backport\Plugin\IconPackManagerInterface', 'Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface');

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Theme\Icon\IconDefinitionInterface;
use Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Tests the ui_icons_menu module.
 *
 * @group ui_icons
 */
class UiIconsMenuTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'menu_link_content',
    'link',
    'ui_icons',
    'ui_icons_backport',
    'ui_icons_menu',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('menu_link_content');
  }

  /**
   * Tests ui_icons_menu_entity_base_field_info_alter().
   */
  public function testEntityBaseFieldInfoAlter(): void {
    $entity_type = $this->container->get('entity_type.manager')->getDefinition('menu_link_content');
    $fields = MenuLinkContent::baseFieldDefinitions($entity_type);
    ui_icons_menu_entity_base_field_info_alter($fields, $entity_type);

    $this->assertArrayHasKey('link', $fields);

    $link_field = $fields['link'];
    $this->assertInstanceOf(BaseFieldDefinition::class, $link_field);

    $form_display_options = $link_field->getDisplayOptions('form');
    $this->assertIsArray($form_display_options);
    $this->assertArrayHasKey('type', $form_display_options);
    $this->assertContains($form_display_options['type'], ['icon_link_widget', 'icon_link_attributes_widget']);
    $this->assertFalse($link_field->isRequired());
  }

  /**
   * Data provider for ::testPreprocessMenu().
   */
  public static function iconDisplayDataProvider(): array {
    return [
      'icon only' => ['icon_only', ['icon']],
      'icon before' => ['before', ['icon', 'title']],
      'icon after' => ['after', ['title', 'icon']],
    ];
  }

  /**
   * Tests ui_icons_menu_preprocess_menu().
   *
   * @dataProvider iconDisplayDataProvider
   */
  public function testPreprocessMenu(?string $iconDisplay, array $expectedOrder): void {
    // Create a mock menu item.
    $title = 'Test Item';
    $markup = '<img class="icon drupal-icon" src="" />';

    $menu_link = MenuLinkContent::create([
      'title' => $title,
      'link' => ['uri' => 'internal:/'],
    ]);
    $menu_link->save();

    $variables = [
      'items' => [
        [
          'url' => $menu_link->getUrlObject(),
          'title' => $menu_link->getTitle(),
          'below' => [],
        ],
      ],
    ];
    // Set icon options.
    $url = $variables['items'][0]['url'];
    $options = $url->getOptions();

    $options['icon'] = ['target_id' => 'test_pack:test_icon'];
    if ($iconDisplay !== NULL) {
      $options['icon_display'] = $iconDisplay;
    }
    $url->setOptions($options);

    // Create a mock IconDefinitionInterface.
    $icon = $this->createMock(IconDefinitionInterface::class);
    $icon->method('getPackId')->willReturn('test_pack');
    $icon->method('getRenderable')->willReturn(['#markup' => $markup]);

    // Mock the icon pack manager service.
    $icon_pack_manager = $this->createMock(IconPackManagerInterface::class);
    $icon_pack_manager->method('getIcon')->willReturn($icon);
    $this->container->set('plugin.manager.icon_pack', $icon_pack_manager);

    ui_icons_menu_preprocess_menu($variables);

    switch ($iconDisplay) {
      case 'icon_only':
        $expected = $markup;
        break;

      case 'before':
        $expected = $markup . '&nbsp;<span class="ui-icons-menu-text">' . $title . '</span>';
        break;

      case 'after':
        $expected = '<span class="ui-icons-menu-text">' . $title . '</span>&nbsp;' . $markup;
        break;
    }

    $this->assertSame(
      $expected,
      (string) $variables['items'][0]['title']
    );
  }

}
