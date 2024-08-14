<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons\Unit\Element;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\ui_icons\Unit\IconUnitTestCase;
use Drupal\ui_icons\Element\IconAutocomplete;
use Drupal\ui_icons\IconDefinition;
use Drupal\ui_icons\Plugin\IconPackManagerInterface;

/**
 * Tests IconAutocomplete FormElement class.
 *
 * @group ui_icons
 */
class IconAutocompleteTest extends IconUnitTestCase {

  /**
   * The container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  private ContainerBuilder $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $this->container->set('plugin.manager.ui_icons_pack', $this->createMock(IconPackManagerInterface::class));
    \Drupal::setContainer($this->container);
  }

  /**
   * Test the getInfo method.
   */
  public function testGetInfo(): void {
    $iconAutocomplete = new IconAutocomplete([], 'test', 'test');
    $info = $iconAutocomplete->getInfo();

    $this->assertArrayHasKey('#input', $info);
    $this->assertArrayHasKey('#element_validate', $info);
    $this->assertSame([['Drupal\ui_icons\Element\IconAutocomplete', 'validateIcon']], $info['#element_validate']);

    $this->assertArrayHasKey('#process', $info);
    $this->assertSame(['Drupal\ui_icons\Element\IconAutocomplete', 'processIcon'], $info['#process'][0]);

    $this->assertArrayHasKey('#pre_render', $info);
    $this->assertSame(['Drupal\ui_icons\Element\IconAutocomplete', 'preRenderGroup'], $info['#pre_render'][0]);

    $this->assertArrayHasKey('#theme', $info);
    $this->assertSame('input__icon', $info['#theme']);

    $this->assertArrayHasKey('#allowed_icon_pack', $info);
    $this->assertSame([], $info['#allowed_icon_pack']);

    $this->assertArrayHasKey('#show_settings', $info);
    $this->assertFalse($info['#show_settings']);

    $this->assertArrayHasKey('#default_settings', $info);
    $this->assertSame([], $info['#default_settings']);

    $this->assertArrayHasKey('#settings_title', $info);
    $this->assertEquals(new TranslatableMarkup('Settings'), $info['#settings_title']);
  }

  /**
   * Test the processIcon method.
   */
  public function testProcessIcon(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $complete_form = [];

    $element = [
      '#parents' => ['foo'],
      '#array_parents' => ['bar/foo'],
    ];
    IconAutocomplete::processIcon($element, $form_state, $complete_form);
    $this->assertTrue($element['#tree']);

    $this->assertArrayHasKey('icon_id', $element);
    $this->assertArrayNotHasKey('icon_settings', $element);

    $this->assertArrayHasKey('#autocomplete_route_name', $element['icon_id']);
    $this->assertSame('ui_icons.autocomplete', $element['icon_id']['#autocomplete_route_name']);

    $this->assertArrayHasKey('#error_no_message', $element['icon_id']);
    $this->assertTrue($element['icon_id']['#error_no_message']);

    // Test empty allowed.
    $element = [
      '#parents' => ['foo'],
      '#array_parents' => ['bar/foo'],
      '#allowed_icon_pack' => [],
    ];
    IconAutocomplete::processIcon($element, $form_state, $complete_form);
    $this->assertArrayNotHasKey('#autocomplete_query_parameters', $element['icon_id']);
  }

  /**
   * Test the processIcon method.
   */
  public function testProcessIconSettings(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $complete_form = [];

    $ui_icons_pack_plugin_manager = $this->createMock(IconPackManagerInterface::class);
    $ui_icons_pack_plugin_manager->expects($this->once())->method('getIcon')->willReturn($this->createMockIcon());
    // @todo test with added values to the icon_settings form.
    $ui_icons_pack_plugin_manager->expects($this->once())->method('getExtractorPluginForms');
    $this->container->set('plugin.manager.ui_icons_pack', $ui_icons_pack_plugin_manager);

    $element = [
      '#parents' => ['foo'],
      '#array_parents' => ['bar', 'foo'],
      '#size' => 20,
      '#default_value' => 'test:icon',
      '#allowed_icon_pack' => ['foo', 'bar', 'baz'],
      '#show_settings' => TRUE,
      '#default_settings' => ['foo' => 'bar'],
      '#settings_title' => 'Baz',
      '#placeholder' => 'Qux',
      '#attributes' => ['quux' => 'foo'],
      '#required' => TRUE,
    ];
    IconAutocomplete::processIcon($element, $form_state, $complete_form);

    $this->assertArrayHasKey('icon_id', $element);
    $this->assertEquals($element['#size'], $element['icon_id']['#size']);
    $this->assertEquals($element['#placeholder'], $element['icon_id']['#placeholder']);
    $this->assertEquals($element['#default_value'], $element['icon_id']['#value']);
    $this->assertEquals([$element['#parents']], $element['icon_id']['#limit_validation_errors']);

    $this->assertArrayHasKey('#ajax', $element['icon_id']);
    $this->assertEquals('bar/foo', $element['icon_id']['#ajax']['options']['query']['element_parents']);
    $this->assertEquals('foo+bar+baz', $element['icon_id']['#autocomplete_query_parameters']['allowed_icon_pack']);

    $this->assertArrayHasKey('icon_settings', $element);
    $this->assertEquals('icon[foo]', $element['icon_settings']['#name']);
    $this->assertEquals($element['#settings_title'], $element['icon_settings']['#title']);
  }

  /**
   * Test the validateIcon method.
   *
   * @param array $element
   *   The element data.
   * @param array $values
   *   The values data.
   * @param string $icon_pack_id
   *   The icon set id.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $expected_error
   *   The expected error message or no message.
   *
   * @dataProvider providerValidateIcon
   */
  public function testValidateIcon(array $element, array $values, string $icon_pack_id, ?TranslatableMarkup $expected_error): void {
    $icon = IconDefinition::create(
      'foo:baz',
      'foo/bar',
      [
        'icon_pack_id' => $icon_pack_id,
      ],
    );
    $complete_form = [];

    $ui_icons_pack_plugin_manager = $this->createMock(IconPackManagerInterface::class);
    $ui_icons_pack_plugin_manager->method('getIcon')->willReturn($icon);

    $this->container->set('plugin.manager.ui_icons_pack', $ui_icons_pack_plugin_manager);

    $formState = $this->createMock(FormStateInterface::class);
    $formState->method('getValues')->willReturn($values);
    $formState->expects($this->once())
      ->method('setValueForElement')
      ->with($element, ['icon' => $icon, 'settings' => []]);

    IconAutocomplete::validateIcon($element, $formState, $complete_form);
  }

  /**
   * Provides data for testValidateIcon.
   *
   * @return array
   *   The data to test.
   */
  public static function providerValidateIcon(): array {
    return [
      'valid icon' => [
        [
          '#parents' => ['icon'],
          'icon_id' => [
            '#title' => 'Foo',
          ],
        ],
        [
          'icon' => [
            'icon_id' => 'valid_icon_id',
            'icon_settings' => [],
          ],
        ],
        'foo',
        NULL,
      ],
    ];
  }

  /**
   * Test the validateIcon method.
   */
  public function testValidateIconEmpty(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $complete_form = [];
    $element = ['#parents' => ['foo']];
    IconAutocomplete::validateIcon($element, $form_state, $complete_form);
    $this->assertEquals(['#parents' => ['foo']], $element);
  }

}
