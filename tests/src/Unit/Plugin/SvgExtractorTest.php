<?php

declare(strict_types=1);

namespace Drupal\ui_icons\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\ui_icons\Exception\IconsetConfigErrorException;
use Drupal\ui_icons\IconDefinitionInterface;
use Drupal\ui_icons\Plugin\UiIconsExtractor\SvgExtractor;
use Drupal\ui_icons\UiIconsFinder;

/**
 * Tests ui_icons svg extractor plugin.
 *
 * @group ui_icons
 */
class SvgExtractorTest extends UnitTestCase {

  /**
   * Test the getIcons method.
   */
  public function testGetIconsExceptionSource(): void {
    $svgExtractorPlugin = new SvgExtractor(
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
    $svgExtractorPlugin->getIcons();
  }

  /**
   * Test the getIcons method.
   */
  public function testGetIconsExceptionSourceEmpty(): void {
    $svgExtractorPlugin = new SvgExtractor(
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
    $svgExtractorPlugin->getIcons();
  }

  /**
   * Test the getIcons method.
   */
  public function testGetIconsExceptionPaths(): void {
    $svgExtractorPlugin = new SvgExtractor(
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
    $svgExtractorPlugin->getIcons();
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

    $svgExtractorPlugin = new SvgExtractor(
      [
        'config' => ['sources' => ['foo/bar/baz.svg']],
        '_path_info' => [
          'drupal_root' => '/_ROOT_/web',
          'absolute_path' => '/_ROOT_/web/modules/my_module',
          'relative_path' => 'modules/my_module',
        ],
        'iconset_id' => 'svg',
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $uiIconsFinder,
    );

    $icons = $svgExtractorPlugin->getIcons();
    $this->assertSame("Start tag expected, '<' not found", trim($icons['svg:baz']->getContent()));
  }

  /**
   * Test the getIcons method.
   */
  public function testGetIconsEmpty(): void {
    $uiIconsFinder = $this->createMock(UiIconsFinder::class);
    $uiIconsFinder->method('getFilesFromSource')->willReturn([]);

    $svgExtractorPlugin = new SvgExtractor(
      [
        'config' => ['sources' => ['foo/bar/baz.svg']],
        '_path_info' => [
          'drupal_root' => '/_ROOT_/web',
          'absolute_path' => '/_ROOT_/web/modules/my_module',
          'relative_path' => 'modules/my_module',
        ],
        'iconset_id' => 'svg',
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $uiIconsFinder,
    );
    $icons = $svgExtractorPlugin->getIcons();

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

    $svg_expected = '<title>test</title><g><path d="M8 15a.5.5 0 0 0"/></g>';
    $svg_data = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">' . $svg_expected . '</svg>';
    $uiIconsFinder->method('getFileContents')->willReturn($svg_data);

    $svgExtractorPlugin = new SvgExtractor(
      [
        'config' => ['sources' => ['foo/bar/baz.svg']],
        '_path_info' => [
          'drupal_root' => '/_ROOT_/web',
          'absolute_path' => '/_ROOT_/web/modules/my_module',
          'relative_path' => 'modules/my_module',
        ],
        'iconset_id' => 'svg',
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $uiIconsFinder,
    );
    $icons = $svgExtractorPlugin->getIcons();

    $this->assertisArray($icons);
    $this->assertArrayHasKey('svg:baz', $icons);

    $this->assertInstanceOf(IconDefinitionInterface::class, $icons['svg:baz']);

    $this->assertSame($svg_expected, $icons['svg:baz']->getContent());
  }

}
