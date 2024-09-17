<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons\Unit;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\ui_icons\IconFinder;

/**
 * Tests IconFinder class.
 *
 * @todo move path tests from IconExtractorWithFinderTest.
 *
 * @group ui_icons
 */
class IconFinderTest extends UnitTestCase {

  /**
   * The file system mock.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private FileSystemInterface $fileSystem;

  /**
   * The IconFinder instance.
   *
   * @var \Drupal\ui_icons\IconFinder
   */
  private IconFinder $iconFinder;

  /**
   * The file system mock.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->fileUrlGenerator = $this->createMock(FileUrlGeneratorInterface::class);

    $this->iconFinder = new IconFinder(
      $this->fileSystem,
      $this->fileUrlGenerator
    );
  }

  /**
   * Test the getFilesFromHttpUrl method.
   *
   * @param string $url
   *   The url to test.
   * @param bool $expected_result
   *   The expected result.
   * @param string $expected_icon_id
   *   The expected icon id.
   *
   * @dataProvider providerGetFilesFromHttpUrl
   */
  public function testGetFilesFromHttpUrl(string $url, bool $expected_result, string $expected_icon_id = ''): void {
    $result = $this->iconFinder->getFilesFromSource($url, '', '', '');

    if ($expected_result) {
      $this->assertArrayHasKey('icon', $result);
      $this->assertEquals($expected_icon_id, $result['icon']['icon_id']);
      $this->assertEquals($url, $result['icon']['absolute_path']);
    }
    else {
      $this->assertEmpty($result);
    }
  }

  /**
   * Provider for the getFilesFromHttpUrl method.
   *
   * @return array
   *   The data to test.
   */
  public static function providerGetFilesFromHttpUrl(): array {
    return [
      ['http://example.com/icons/icon.svg', TRUE, 'icon'],
      ['https://example.com/icons/icon.svg', TRUE, 'icon'],
      ['path/to/icon.svg', FALSE],
      ['/path/to/icon.svg', FALSE],
    ];
  }

  /**
   * Test the extractIconId method.
   */
  public function testExtractIconId(): void {
    $method = new \ReflectionMethod(IconFinder::class, 'extractIconId');
    $method->setAccessible(TRUE);

    $this->assertEquals('icon', $method->invoke($this->iconFinder, '{icon_id}.svg', 'icon.svg'));
    $this->assertEquals('my-icon', $method->invoke($this->iconFinder, 'prefix-{icon_id}.svg', 'prefix-my-icon.svg'));
    $this->assertNull($method->invoke($this->iconFinder, 'static-name.svg', 'different-name.svg'));
  }

  /**
   * Test the determineGroup method.
   */
  public function testDetermineGroup(): void {
    $method = new \ReflectionMethod(IconFinder::class, 'determineGroup');
    $method->setAccessible(TRUE);

    $this->assertEquals('group1', $method->invoke($this->iconFinder, '/path/to/group1/icon.svg', TRUE, TRUE, '{group}'));
    $this->assertEquals('group2', $method->invoke($this->iconFinder, '/path/to/group2/subdir/icon.svg', TRUE, FALSE, '/path/to/{group}/subdir'));
    $this->assertEquals('', $method->invoke($this->iconFinder, '/path/to/icon.svg', FALSE, FALSE, ''));
  }

}
