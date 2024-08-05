<?php

declare(strict_types=1);

namespace Drupal\ui_icons_field\Unit;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\ui_icons_field\Plugin\Field\FieldType\UiIconType;

/**
 * Defines a test for the UiIconType field-type.
 *
 * @group ui_icons
 */
class UiIconTypeTest extends UnitTestCase {

  /**
   * The field type.
   *
   * @var \Drupal\ui_icons_field\Plugin\Field\FieldType\UiIconType
   */
  protected $uiIconType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $definition = $this->createMock(ComplexDataDefinitionInterface::class);
    $definition->method('getPropertyDefinitions')->willReturn([]);

    $this->uiIconType = new UiIconType(
      $definition,
      'test',
    );
  }

  /**
   * Test the schema method.
   */
  public function testSchema(): void {
    $schema = $this->uiIconType::schema($this->createMock(FieldStorageDefinitionInterface::class));

    $this->assertCount(3, $schema['columns']);
    $this->assertArrayHasKey('iconset_id', $schema['columns']);
    $this->assertArrayHasKey('icon_id', $schema['columns']);
    $this->assertArrayHasKey('settings', $schema['columns']);
  }

  /**
   * Test the propertyDefinitions method.
   */
  public function testPropertyDefinitions(): void {
    $properties = $this->uiIconType::propertyDefinitions($this->createMock(FieldStorageDefinitionInterface::class));

    $this->assertSame('string', $properties['iconset_id']->getDataType());
    $this->assertSame('string', $properties['icon_id']->getDataType());
    $this->assertSame('string', $properties['settings']->getDataType());
  }

}
