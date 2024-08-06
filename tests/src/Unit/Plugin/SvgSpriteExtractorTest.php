<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\ui_icons\Exception\IconsetConfigErrorException;
use Drupal\ui_icons\IconDefinitionInterface;
use Drupal\ui_icons\Plugin\UiIconsExtractor\SvgSpriteExtractor;
use Drupal\ui_icons\UiIconsFinder;

/**
 * Tests ui_icons svg_sprite extractor plugin.
 *
 * @group ui_icons
 */
class SvgSpriteExtractorTest extends UnitTestCase {

  /**
   * Test the getIcons method.
   */
  public function testGetIconsExceptionSource(): void {
    $svgSpriteExtractorPlugin = new SvgSpriteExtractor(
      [],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $this->createMock(UiIconsFinder::class),
    );
    $this->expectException(IconsetConfigErrorException::class);
    $this->expectExceptionMessage('Missing `config: sources` in your definition, extractor test_extractor require this value.');
    $svgSpriteExtractorPlugin->getIcons();
  }

  /**
   * Test the getIcons method.
   */
  public function testGetIconsExceptionSourceEmpty(): void {
    $svgSpriteExtractorPlugin = new SvgSpriteExtractor(
      [
        'config' => ['sources' => []],
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $this->createMock(UiIconsFinder::class),
    );
    $this->expectException(IconsetConfigErrorException::class);
    $this->expectExceptionMessage('Missing `config: sources` in your definition, extractor test_extractor require this value.');
    $svgSpriteExtractorPlugin->getIcons();
  }

  /**
   * Test the getIcons method.
   */
  public function testGetIconsExceptionPaths(): void {
    $svgSpriteExtractorPlugin = new SvgSpriteExtractor(
      [
        'config' => ['sources' => ['foo/bar']],
        '_path_info' => [],
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $this->createMock(UiIconsFinder::class),
    );
    $this->expectException(IconsetConfigErrorException::class);
    $this->expectExceptionMessage('Could not retrieve paths for extractor test_extractor.');
    $svgSpriteExtractorPlugin->getIcons();
  }

  /**
   * Test the getIcons method.
   */
  public function testGetIconsInvalid(): void {
    $uiIconsFinder = $this->createMock(UiIconsFinder::class);

    $icons_list = [
      'baz' => [
        'name' => 'baz',
        'icon_id' => 'baz',
        'relative_path' => 'web/modules/my_module/foo/bar/baz.svg',
        'absolute_path' => '/_ROOT_/web/modules/my_module/foo/bar/baz.svg',
        'group' => NULL,
      ],
    ];
    $uiIconsFinder->method('getFilesFromSource')->willReturn($icons_list);
    $svg_data = 'Not valid svg';
    $uiIconsFinder->method('getFileContents')->willReturn($svg_data);

    $svgSpriteExtractorPlugin = new SvgSpriteExtractor(
      [
        'config' => ['sources' => ['foo/bar/baz.svg']],
        '_path_info' => [
          'drupal_root' => '/_ROOT_/web',
          'absolute_path' => '/_ROOT_/web/modules/my_module',
          'relative_path' => 'modules/my_module',
        ],
        'iconset_id' => 'svg_sprite',
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $uiIconsFinder,
    );

    $icons = $svgSpriteExtractorPlugin->getIcons();
    $this->assertIsArray($icons);
    $this->assertArrayHasKey("svg_sprite:Start tag expected, '<' not found", $icons);
  }

  /**
   * Test the getIcons method.
   */
  public function testGetIconsEmpty(): void {
    $uiIconsFinder = $this->createMock(UiIconsFinder::class);
    $uiIconsFinder->method('getFilesFromSource')->willReturn([]);

    $svgSpriteExtractorPlugin = new SvgSpriteExtractor(
      [
        'config' => ['sources' => ['foo/bar/baz.svg']],
        '_path_info' => [
          'drupal_root' => '/_ROOT_/web',
          'absolute_path' => '/_ROOT_/web/modules/my_module',
          'relative_path' => 'modules/my_module',
        ],
        'iconset_id' => 'svg_sprite',
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $uiIconsFinder,
    );
    $icons = $svgSpriteExtractorPlugin->getIcons();

    $this->assertEmpty($icons);
  }

  /**
   * Test the getIcons method.
   */
  public function testGetIcons(): void {
    $uiIconsFinder = $this->createMock(UiIconsFinder::class);

    $icons_list = [
      'baz' => [
        'name' => 'baz',
        'icon_id' => 'baz',
        'relative_path' => 'web/modules/my_module/foo/bar/baz.svg',
        'absolute_path' => '/_ROOT_/web/modules/my_module/foo/bar/baz.svg',
        'group' => NULL,
      ],
    ];
    $uiIconsFinder->method('getFilesFromSource')->willReturn($icons_list);

    $svg_expected = '<title>test</title><symbol id="foo"></symbol><symbol id="bar"></symbol>';
    $svg_data = '<svg xmlns="http://www.w3.org/2000/svg">' . $svg_expected . '</svg>';
    $uiIconsFinder->method('getFileContents')->willReturn($svg_data);

    $svgSpriteExtractorPlugin = new SvgSpriteExtractor(
      [
        'config' => ['sources' => ['foo/bar/baz.svg']],
        '_path_info' => [
          'drupal_root' => '/_ROOT_/web',
          'absolute_path' => '/_ROOT_/web/modules/my_module',
          'relative_path' => 'modules/my_module',
        ],
        'iconset_id' => 'svg_sprite',
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $uiIconsFinder,
    );
    $icons = $svgSpriteExtractorPlugin->getIcons();

    $this->assertIsArray($icons);
    $this->assertCount(2, $icons);
    $this->assertArrayHasKey('svg_sprite:foo', $icons);
    $this->assertArrayHasKey('svg_sprite:bar', $icons);

    $this->assertInstanceOf(IconDefinitionInterface::class, $icons['svg_sprite:foo']);
    $this->assertInstanceOf(IconDefinitionInterface::class, $icons['svg_sprite:bar']);

    $this->assertSame('foo', $icons['svg_sprite:foo']->getName());
    $this->assertSame('web/modules/my_module/foo/bar/baz.svg', $icons['svg_sprite:foo']->getSource());
  }

}