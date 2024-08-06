<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons\Unit\Element;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\ui_icons\Element\UiIconAutocomplete;
use Drupal\ui_icons\IconDefinition;
use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;

/**
 * Tests UiIconAutocomplete FormElement class.
 *
 * @group ui_icons
 */
class UiIconAutocompleteTest extends UnitTestCase {

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
    $this->container->set('plugin.manager.ui_iconset', $this->createMock(UiIconsetManagerInterface::class));
    \Drupal::setContainer($this->container);
  }

  /**
   * Test the getInfo method.
   */
  public function testGetInfo(): void {
    $uiIconAutocomplete = new UiIconAutocomplete([], 'test', 'test');
    $info = $uiIconAutocomplete->getInfo();

    $this->assertArrayHasKey('#input', $info);
    $this->assertArrayHasKey('#element_validate', $info);
    $this->assertSame([['Drupal\ui_icons\Element\UiIconAutocomplete', 'validateIcon']], $info['#element_validate']);

    $this->assertArrayHasKey('#process', $info);
    $this->assertSame(['Drupal\ui_icons\Element\UiIconAutocomplete', 'processIcon'], $info['#process'][0]);

    $this->assertArrayHasKey('#pre_render', $info);
    $this->assertSame(['Drupal\ui_icons\Element\UiIconAutocomplete', 'preRenderGroup'], $info['#pre_render'][0]);

    $this->assertArrayHasKey('#theme', $info);
    $this->assertSame('input__icon', $info['#theme']);

    $this->assertArrayHasKey('#allowed_iconset', $info);
    $this->assertSame([], $info['#allowed_iconset']);

    $this->assertArrayHasKey('#show_settings', $info);
    $this->assertFalse($info['#show_settings']);
  }

  /**
   * Test the processIcon method.
   */
  public function testProcessIcon(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $complete_form = [];

    $element = [];
    UiIconAutocomplete::processIcon($element, $form_state, $complete_form);
    $this->assertTrue($element['#tree']);

    $this->assertArrayHasKey('icon_id', $element);
    $this->assertArrayNotHasKey('icon_settings', $element);

    $this->assertArrayHasKey('#autocomplete_route_name', $element['icon_id']);
    $this->assertSame('ui_icons.autocomplete', $element['icon_id']['#autocomplete_route_name']);

    $this->assertArrayHasKey('#error_no_message', $element['icon_id']);
    $this->assertTrue($element['icon_id']['#error_no_message']);

    // Test empty allowed.
    $element = [
      '#allowed_iconset' => [],
    ];
    UiIconAutocomplete::processIcon($element, $form_state, $complete_form);
    $this->assertArrayNotHasKey('#autocomplete_query_parameters', $element['icon_id']);
  }

  /**
   * Test the processIcon method.
   */
  public function testProcessIconSettings(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $complete_form = [];

    $ui_iconset_plugin_manager = $this->createMock(UiIconsetManagerInterface::class);
    $ui_iconset_plugin_manager->expects($this->once())->method('getExtractorPluginForms');
    $this->container->set('plugin.manager.ui_iconset', $ui_iconset_plugin_manager);

    $element = [
      '#size' => 20,
      '#default_value' => 'test:icon',
      '#allowed_iconset' => ['foo', 'bar', 'baz'],
      '#show_settings' => TRUE,
      '#default_settings' => ['foo' => 'bar'],
      '#settings_title' => 'Baz',
      '#placeholder' => 'Qux',
      '#attributes' => ['quux' => 'foo'],
      '#required' => TRUE,
    ];
    UiIconAutocomplete::processIcon($element, $form_state, $complete_form);

    $expected_icon = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Icon'),
      '#placeholder' => $element['#placeholder'],
      '#title_display' => 'invisible',
      '#autocomplete_route_name' => 'ui_icons.autocomplete',
      '#attributes' => $element['#attributes'],
      '#required' => $element['#required'],
      '#size' => $element['#size'],
      '#maxlength' => 128,
      '#error_no_message' => TRUE,
      '#autocomplete_query_parameters' => ['allowed_iconset' => 'foo+bar+baz'],
    ];
    $this->assertEquals($expected_icon, $element['icon_id']);

    $this->assertArrayHasKey('icon_settings', $element);
  }

  /**
   * Test the validateIcon method.
   *
   * @param array $element
   *   The element data.
   * @param array $values
   *   The values data.
   * @param string $iconset_id
   *   The icon set id.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $expected_error
   *   The expected error message or no message.
   *
   * @dataProvider providerValidateIcon
   */
  public function testValidateIcon(array $element, array $values, string $iconset_id, ?TranslatableMarkup $expected_error): void {
    $icon = IconDefinition::create(
      'foo:baz',
      'foo/bar',
      [
        'iconset_id' => $iconset_id,
      ],
    );
    $complete_form = [];

    $ui_iconset_plugin_manager = $this->createMock(UiIconsetManagerInterface::class);
    $ui_iconset_plugin_manager->method('getIcon')->willReturn($icon);

    $this->container->set('plugin.manager.ui_iconset', $ui_iconset_plugin_manager);

    $formState = $this->createMock(FormStateInterface::class);
    $formState->method('getValues')->willReturn($values);
    $formState->expects($this->once())
      ->method('setValueForElement')
      ->with($element, ['icon' => $icon, 'settings' => []]);

    UiIconAutocomplete::validateIcon($element, $formState, $complete_form);
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
    UiIconAutocomplete::validateIcon($element, $form_state, $complete_form);
    $this->assertEquals(['#parents' => ['foo']], $element);
  }

}
