<?php

declare(strict_types=1);

namespace Drupal\ui_icons_field\Unit\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\ui_icons_field\Plugin\Field\FieldWidget\UiIconWidget;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Tests the UiIconWidget field class.
 *
 * @group ui_icons
 */
class UiIconWidgetTest extends UnitTestCase {

  /**
   * The field widget under test.
   *
   * @var \Drupal\ui_icons_field\Plugin\Field\FieldWidget\UiIconWidget
   */
  protected UiIconWidget $widget;

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected FieldDefinitionInterface $fieldDefinition;

  /**
   * The UiIconsetManager instance.
   *
   * @var \Drupal\ui_icons\Plugin\UiIconsetManagerInterface
   */
  protected UiIconsetManagerInterface $pluginManagerUiIconset;

  /**
   * The container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected ContainerBuilder $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->createMock(TranslationInterface::class));
    \Drupal::setContainer($this->container);

    $this->pluginManagerUiIconset = $this->createMock(UiIconsetManagerInterface::class);
    $this->fieldDefinition = $this->createMock(FieldDefinitionInterface::class);

    $this->widget = new UiIconWidget(
      'ui_icon_widget',
      [],
      $this->fieldDefinition,
      [],
      [],
      $this->pluginManagerUiIconset
    );
  }

  /**
   * Tests the default settings of the widget.
   */
  public function testDefaultSettings(): void {
    $expected = [
      'allowed_iconset' => [],
      'icon_settings' => FALSE,
    ];
    $this->assertEquals($expected, $this->widget->defaultSettings());
  }

  /**
   * Tests the settings form.
   */
  public function testSettingsForm(): void {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    $form = $this->widget->settingsForm($form, $form_state);

    $this->assertArrayHasKey('allowed_iconset', $form);
    $this->assertArrayHasKey('icon_settings', $form);
  }

  /**
   * Tests the settings summary.
   */
  public function testSettingsSummary(): void {
    $this->widget->setSetting('allowed_iconset', ['iconset1' => 'iconset1']);
    $this->widget->setSetting('icon_settings', TRUE);

    $summary = $this->widget->settingsSummary();

    $this->assertStringContainsString('With Icon set:', $summary[0]->getUntranslatedString());
    $this->assertSame('Icon settings enabled', $summary[1]->getUntranslatedString());

    $this->widget->setSetting('allowed_iconset', []);

    $summary = $this->widget->settingsSummary();

    $this->assertStringContainsString('All icon sets available for selection', $summary[0]->getUntranslatedString());
  }

  /**
   * Data provider for testMassageFormValues.
   */
  public static function providerMassageFormValues(): array {
    return [
      'case 1' => [
        'values' => [
          0 => ['icon' => 'iconset1:icon1', 'settings' => ['setting1' => 'value1']],
          1 => ['icon' => 'iconset2:icon2', 'settings' => []],
        ],
        'expected' => [
          0 => [
            'icon' => 'iconset1:icon1',
            'settings' => serialize(['setting1' => 'value1']),
            'delta' => 0,
            'iconset_id' => 'iconset1',
            'icon_id' => 'icon1',
          ],
          1 => [
            'icon' => 'iconset2:icon2',
            'settings' => serialize([]),
            'delta' => 1,
            'iconset_id' => 'iconset2',
            'icon_id' => 'icon2',
          ],
        ],
      ],
      'case 2' => [
        'values' => [
          0 => ['icon' => 'iconset3:icon3', 'settings' => ['setting3' => 'value3']],
        ],
        'expected' => [
          0 => [
            'icon' => 'iconset3:icon3',
            'settings' => serialize(['setting3' => 'value3']),
            'delta' => 0,
            'iconset_id' => 'iconset3',
            'icon_id' => 'icon3',
          ],
        ],
      ],
      'case invalid icon' => [
        'values' => [
          0 => ['icon' => 'icon3', 'settings' => ['setting3' => 'value3']],
        ],
        'expected' => [],
      ],
    ];
  }

  /**
   * Tests the massageFormValues method.
   *
   * @dataProvider providerMassageFormValues
   *
   * @param array $values
   *   The data values.
   * @param array $expected
   *   The data expected.
   */
  public function testMassageFormValues(array $values, array $expected): void {
    $form_state = $this->createMock(FormStateInterface::class);

    $result = $this->widget->massageFormValues($values, [], $form_state);

    $this->assertEquals($expected, $result);
  }

}
