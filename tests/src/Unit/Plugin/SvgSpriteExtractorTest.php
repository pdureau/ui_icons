<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\ui_icons\Exception\IconPackConfigErrorException;
use Drupal\ui_icons\IconDefinitionInterface;
use Drupal\ui_icons\IconFinder;
use Drupal\ui_icons\Plugin\IconExtractor\SvgSpriteExtractor;

/**
 * Tests ui_icons svg_sprite extractor plugin.
 *
 * @group ui_icons
 */
class SvgSpriteExtractorTest extends UnitTestCase {

  /**
   * Test the getIcons method.
   */
  public function testDiscoverIconsExceptionSource(): void {
    $svgSpriteExtractorPlugin = new SvgSpriteExtractor(
      [],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $this->createMock(IconFinder::class),
    );
    $this->expectException(IconPackConfigErrorException::class);
    $this->expectExceptionMessage('Missing `config: sources` in your definition, extractor test_extractor require this value.');
    $svgSpriteExtractorPlugin->discoverIcons();
  }

  /**
   * Test the getIcons method.
   */
  public function testDiscoverIconsExceptionSourceEmpty(): void {
    $svgSpriteExtractorPlugin = new SvgSpriteExtractor(
      [
        'config' => ['sources' => []],
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $this->createMock(IconFinder::class),
    );
    $this->expectException(IconPackConfigErrorException::class);
    $this->expectExceptionMessage('Missing `config: sources` in your definition, extractor test_extractor require this value.');
    $svgSpriteExtractorPlugin->discoverIcons();
  }

  /**
   * Test the getIcons method.
   */
  public function testDiscoverIconsExceptionRelativePath(): void {
    $svgSpriteExtractorPlugin = new SvgSpriteExtractor(
      [
        'config' => ['sources' => ['foo/bar']],
        'definition_relative_path' => '',
        'definition_absolute_path' => '',
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $this->createMock(IconFinder::class),
    );
    $this->expectException(IconPackConfigErrorException::class);
    $this->expectExceptionMessage('Empty relative path for extractor test_extractor.');
    $svgSpriteExtractorPlugin->discoverIcons();
  }

  /**
   * Test the getIcons method.
   */
  public function testDiscoverIconsInvalid(): void {
    $iconFinder = $this->createMock(IconFinder::class);

    $icons_list = [
      'baz' => [
        'name' => 'baz',
        'icon_id' => 'baz',
        'source' => 'web/modules/my_module/foo/bar/baz.svg',
        'absolute_path' => '/_ROOT_/web/modules/my_module/foo/bar/baz.svg',
        'group' => NULL,
      ],
    ];
    $iconFinder->method('getFilesFromSources')->willReturn($icons_list);
    $svg_data = 'Not valid svg';
    $iconFinder->method('getFileContents')->willReturn($svg_data);

    $svgSpriteExtractorPlugin = new SvgSpriteExtractor(
      [
        'config' => ['sources' => ['foo/bar/baz.svg']],
        'definition_relative_path' => 'modules/my_module',
        'definition_absolute_path' => '/_ROOT_/web/modules/my_module',
        'icon_pack_id' => 'svg_sprite',
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $iconFinder,
    );

    $icons = $svgSpriteExtractorPlugin->discoverIcons();
    $this->assertArrayHasKey("svg_sprite:Start tag expected, '<' not found", $icons);
  }

  /**
   * Test the getIcons method.
   */
  public function testDiscoverIconsEmpty(): void {
    $iconFinder = $this->createMock(IconFinder::class);
    $iconFinder->method('getFilesFromSources')->willReturn([]);

    $svgSpriteExtractorPlugin = new SvgSpriteExtractor(
      [
        'config' => ['sources' => ['foo/bar/baz.svg']],
        'definition_relative_path' => 'modules/my_module',
        'definition_absolute_path' => '/_ROOT_/web/modules/my_module',
        'icon_pack_id' => 'svg_sprite',
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $iconFinder,
    );
    $icons = $svgSpriteExtractorPlugin->discoverIcons();

    $this->assertEmpty($icons);
  }

  /**
   * Test the getIcons method.
   *
   * @param string $svg
   *   The svg to test.
   * @param int $expected_count
   *   The number of icon expected.
   * @param array $expected_icon
   *   The icon ids expected.
   *
   * @dataProvider providerDiscoverIcons
   */
  public function testDiscoverIcons(string $svg, int $expected_count, array $expected_icon): void {
    $extractor_id = 'svg_sprite';
    $iconFinder = $this->createMock(IconFinder::class);

    $icons_list = [
      'baz' => [
        'name' => 'baz',
        'icon_id' => 'baz',
        'source' => 'web/modules/my_module/foo/bar/baz.svg',
        'absolute_path' => '/_ROOT_/web/modules/my_module/foo/bar/baz.svg',
        'group' => NULL,
      ],
    ];
    $iconFinder->method('getFilesFromSources')->willReturn($icons_list);

    $svg_data = '<svg xmlns="http://www.w3.org/2000/svg">' . $svg . '</svg>';
    $iconFinder->method('getFileContents')->willReturn($svg_data);

    $svgSpriteExtractorPlugin = new SvgSpriteExtractor(
      [
        'config' => ['sources' => ['foo/bar/baz.svg']],
        'definition_relative_path' => 'modules/my_module',
        'definition_absolute_path' => '/_ROOT_/web/modules/my_module',
        'icon_pack_id' => $extractor_id,
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $iconFinder,
    );
    $icons = $svgSpriteExtractorPlugin->discoverIcons();

    $this->assertCount($expected_count, $icons);

    if (!empty($expected_icon)) {
      foreach ($expected_icon as $icon) {
        $key = $extractor_id . ':' . $icon;
        $this->assertArrayHasKey($key, $icons);
        $this->assertInstanceOf(IconDefinitionInterface::class, $icons[$key]);

        $this->assertSame($icon, $icons[$key]->getIconId());
        $this->assertSame('web/modules/my_module/foo/bar/baz.svg', $icons[$key]->getSource());
      }
    }
  }

  /**
   * Data provider for testDiscoverIcons().
   */
  public static function providerDiscoverIcons() {
    return [
      [
        '',
        0,
        [],
      ],
      [
        '<symbol id="foo"></symbol>',
        1,
        ['foo'],
      ],
      [
        '<symbol id="foo"></symbol><symbol id="bar"></symbol>',
        2,
        ['foo', 'bar'],
      ],
      [
        '<defs><symbol id="foo"></symbol></defs>',
        1,
        ['foo'],
      ],
      [
        '<defs><symbol id="foo"></symbol><symbol id="bar"></symbol></defs>',
        2,
        ['foo', 'bar'],
      ],
    ];
  }

}
