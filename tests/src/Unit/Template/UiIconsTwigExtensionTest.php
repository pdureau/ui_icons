<?php

declare(strict_types=1);

use Drupal\ui_icons\IconDefinitionInterface;
use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;
use Drupal\ui_icons\Template\UiIconsTwigExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

/**
 * Test the class UiIconsTwigExtension.
 */
class UiIconsTwigExtensionTest extends TestCase {
  private $pluginManagerUiIconset;
  private $uiIconsTwigExtension;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->pluginManagerUiIconset = $this->createMock(UiIconsetManagerInterface::class);
    $this->uiIconsTwigExtension = new UiIconsTwigExtension($this->pluginManagerUiIconset);
  }

  /**
   * Test the getFunctions method.
   */
  public function testGetFunctions() {
    $functions = $this->uiIconsTwigExtension->getFunctions();
    $this->assertIsArray($functions);
    $this->assertCount(1, $functions);
    $this->assertInstanceOf(TwigFunction::class, $functions[0]);
    $this->assertEquals('icon', $functions[0]->getName());
  }

  /**
   * Test the getIconRenderable method.
   */
  public function testGetIconRenderableReturnsEmptyArrayWhenIconNotFound() {
    $this->pluginManagerUiIconset
      ->method('getIcon')
      ->willReturn(NULL);

    $result = $this->uiIconsTwigExtension->getIconRenderable('iconset_id', 'icon_id');
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Test the getIconRenderable method.
   */
  public function testGetIconRenderableReturnsRenderableArray() {
    $iconMock = $this->createMock(IconDefinitionInterface::class);
    $iconMock->method('getRenderable')
      ->willReturn(['rendered_icon']);

    $this->pluginManagerUiIconset
      ->method('getIcon')
      ->willReturn($iconMock);

    $result = $this->uiIconsTwigExtension->getIconRenderable('iconset_id', 'icon_id');
    $this->assertIsArray($result);
    $this->assertEquals(['rendered_icon'], $result);
  }

}
