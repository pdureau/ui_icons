<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons_field\Unit\Plugin;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\ui_icons\Unit\UiIconsUnitTestCase;
use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;
use Drupal\ui_icons_field\Plugin\Field\FieldWidget\UiIconWidget;

/**
 * Tests the UiIconWidget field class.
 *
 * @group ui_icons
 */
class UiIconWidgetTest extends UiIconsUnitTestCase {

  /**
   * The field widget under test.
   *
   * @var \Drupal\ui_icons_field\Plugin\Field\FieldWidget\UiIconWidget
   */
  private UiIconWidget $widget;

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  private FieldDefinitionInterface $fieldDefinition;

  /**
   * The UiIconsetManager instance.
   *
   * @var \Drupal\ui_icons\Plugin\UiIconsetManagerInterface
   */
  private UiIconsetManagerInterface $pluginManagerUiIconset;

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
      'show_settings' => FALSE,
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
    $this->assertArrayHasKey('show_settings', $form);
  }

  /**
   * Tests the settings summary.
   */
  public function testSettingsSummary(): void {
    $this->widget->setSetting('allowed_iconset', ['iconset1' => 'iconset1']);
    $this->widget->setSetting('show_settings', TRUE);

    $summary = $this->widget->settingsSummary();

    $this->assertStringContainsString('With Icon set:', $summary[0]->getUntranslatedString());
    $this->assertSame('Icon settings enabled', $summary[1]->getUntranslatedString());

    $this->widget->setSetting('allowed_iconset', []);

    $summary = $this->widget->settingsSummary();

    $this->assertStringContainsString('All icon sets available for selection', $summary[0]->getUntranslatedString());
  }

  /**
   * Tests the massageFormValues method.
   */
  public function testMassageFormValues(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $values = [];

    // Invalid icon.
    $values[]['value'] = [
      'icon' => NULL,
    ];

    // Icon without settings.
    $values[]['value'] = [
      'icon' => $this->createMockIcon([
        'iconset_id' => 'foo',
        'icon_id' => 'bar',
      ]),
      'settings' => [],
    ];

    // Icon with settings.
    $values[]['value'] = [
      'icon' => $this->createMockIcon([
        'iconset_id' => 'foo',
        'icon_id' => 'bar',
      ]),
      'settings' => ['baz' => 'qux'],
    ];

    $actual = $this->widget->massageFormValues($values, [], $form_state);

    foreach ($actual as $delta => $value) {
      if (NULL === $values[$delta]['value']['icon']) {
        $this->assertNull($value['target_id']);
        $this->assertEmpty($value['settings']);
      }
      else {
        $this->assertInstanceOf('Drupal\ui_icons\IconDefinitionInterface', $value['value']['icon']);
        $this->assertEquals('foo:bar', $value['target_id']);
        $this->assertEquals($values[$delta]['value']['settings'], $value['settings']);
      }
    }
  }

}
