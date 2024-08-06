<?php

declare(strict_types=1);

namespace Drupal\ui_icons_field\Kernel\Plugin;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;
use Drupal\ui_icons_field\Plugin\Field\FieldWidget\UiIconWidget;

/**
 * Tests the UiIconWidget field class.
 *
 * @group ui_icons
 */
class UiIconWidgetTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'system',
    'user',
    'entity_test',
    'ui_icons',
    'ui_icons_field',
    'ui_icons_test',
  ];

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
   * The base field definition.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition
   */
  private BaseFieldDefinition $baseField;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pluginManagerUiIconset = $this->container->get('plugin.manager.ui_iconset');

    // @todo test with entity_test_rev?
    $this->baseField = BaseFieldDefinition::create('ui_icon')
      ->setName('icon');
    $this->container->get('state')->set('entity_test.additional_base_field_definitions', [
      'icon' => $this->baseField,
    ]);

    $this->installEntitySchema('entity_test');
    $this->installConfig(['system']);

    $this->fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $this->fieldDefinition->method('getFieldStorageDefinition')
      ->willReturn($this->createMock(FieldStorageDefinitionInterface::class));

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
   * Tests the formElement method.
   */
  public function testFormElement(): void {
    $entity = EntityTest::create([
      'name' => 'sample entity',
    ]);
    $entity->save();

    $element = $this->buildWidgetForm($entity);

    $this->assertArrayHasKey('value', $element);
    $this->assertSame('ui_icon_autocomplete', $element['value']['#type']);
    $this->assertNull($element['value']['#default_value']);
    $this->assertEmpty($element['value']['#allowed_iconset']);
    $this->assertFalse($element['value']['#show_settings']);
    $this->assertFalse($element['value']['#required']);
  }

  /**
   * Tests the formElement method.
   */
  public function testFormElementWithSettings(): void {
    $entity = EntityTest::create([
      'name' => 'sample entity',
    ]);
    $entity->save();

    $element = $this->buildWidgetForm($entity, [
      'show_settings' => TRUE,
      'allowed_iconset' => [
        'foo' => 'bar',
        'baz' => 0,
      ],
    ]);

    $this->assertArrayHasKey('value', $element);
    $this->assertSame('ui_icon_autocomplete', $element['value']['#type']);
    $this->assertNull($element['value']['#default_value']);
    $this->assertSame(['foo' => 'bar'], $element['value']['#allowed_iconset']);
    $this->assertTrue($element['value']['#show_settings']);
    $this->assertFalse($element['value']['#required']);
  }

  /**
   * Build the icon widget form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to build the form for.
   * @param array $settings
   *   The settings to pass to the widget, default empty array.
   *
   * @return array
   *   A built form array of the icon widget.
   */
  protected function buildWidgetForm($entity, array $settings = []): array {
    $form = [
      '#parents' => [],
    ];
    return $this->container->get('plugin.manager.field.widget')->createInstance('ui_icon_widget', [
      'field_definition' => $this->baseField,
      'settings' => $settings,
      'third_party_settings' => [],
    ])->formElement($entity->icon, 0, ['#description' => ''], $form, new FormState());
  }

}