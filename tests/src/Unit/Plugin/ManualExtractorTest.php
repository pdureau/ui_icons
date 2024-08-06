<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons\Unit;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\ui_icons\Exception\IconsetConfigErrorException;
use Drupal\ui_icons\Plugin\UiIconsExtractor\ManualExtractor;
use Drupal\ui_icons\Plugin\UiIconsExtractorPluginInterface;
use Drupal\ui_icons\UiIconsFinder;

/**
 * Tests ui_icons manual extractor plugin.
 *
 * @group ui_icons
 */
class ManualExtractorTest extends UnitTestCase {

  /**
   * Test the getIcons method.
   */
  public function testGetIconsExceptionIcons(): void {
    $manualExtractorPlugin = new ManualExtractor(
      [],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $this->createMock(UiIconsFinder::class),
    );
    $this->expectException(IconsetConfigErrorException::class);
    $this->expectExceptionMessage('Missing `config: icons` in your definition, extractor test_extractor require this value.');
    $manualExtractorPlugin->getIcons();
  }

  /**
   * Test the getIcons method.
   */
  public function testGetIconsEmpty(): void {
    $uiIconsFinder = $this->createMock(UiIconsFinder::class);
    $uiIconsFinder->method('getFilesFromSource')->willReturn([]);

    $manualExtractorPlugin = new ManualExtractor(
      [
        'config' => [
          'icons' => ['foo'],
        ],
        '_path_info' => [
          'drupal_root' => '/_ROOT_/web',
          'absolute_path' => '/_ROOT_/web/modules/my_module',
          'relative_path' => 'modules/my_module',
        ],
        'iconset_id' => 'manual',
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $uiIconsFinder,
    );
    $icons = $manualExtractorPlugin->getIcons();

    $this->assertEmpty($icons);
  }

  /**
   * Test the getIcons method.
   */
  public function testGetIcons(): void {
    $icons_list = [
      'baz' => [
        'name' => 'baz',
        'icon_id' => 'baz',
        'relative_path' => 'web/modules/my_module/foo/bar/baz.svg',
        'absolute_path' => '/_ROOT_/web/modules/my_module/foo/bar/baz.svg',
        'group' => NULL,
      ],
    ];

    $uiIconsFinder = $this->createMock(UiIconsFinder::class);
    $uiIconsFinder->method('getFilesFromSource')->willReturn($icons_list);

    $manualExtractorPlugin = new ManualExtractor(
      [
        'config' => [
          'icons' => [
            [
              'name' => 'baz',
              'source' => 'foo/bar/baz.svg',
              'group' => 'foo',
            ],
          ],
        ],
        '_path_info' => [
          'drupal_root' => '/_ROOT_/web',
          'absolute_path' => '/_ROOT_/web/modules/my_module',
          'relative_path' => 'modules/my_module',
        ],
        'iconset_id' => 'manual',
      ],
      'test_extractor',
      [
        'label' => 'Test',
        'description' => 'Test description',
      ],
      $uiIconsFinder,
    );

    $this->assertInstanceOf(UiIconsExtractorPluginInterface::class, $manualExtractorPlugin);
    $this->assertSame('Test', $manualExtractorPlugin->label());
    $this->assertSame('Test description', $manualExtractorPlugin->description());

    $icons = $manualExtractorPlugin->getIcons();

    $this->assertIsArray($icons);
    $this->assertArrayHasKey('manual:baz', $icons);

    $this->assertSame('baz', $icons['manual:baz']->getName());
    $this->assertSame('foo/bar/baz.svg', $icons['manual:baz']->getSource());
    $this->assertSame('foo', $icons['manual:baz']->getGroup());
  }

  /**
   * Test the buildConfigurationForm method.
   */
  public function testConfigurationForm(): void {
    $manualExtractorPlugin = new ManualExtractor(
      [],
      'test_extractor',
      [],
      $this->createMock(UiIconsFinder::class),
    );

    $this->assertInstanceOf(UiIconsExtractorPluginInterface::class, $manualExtractorPlugin);

    // Test buildConfigurationForm with no change because of no 'options';
    $form = ['foo'];
    $this->assertSame($form, $manualExtractorPlugin->buildConfigurationForm($form, new FormState()));

    $manualExtractorPlugin->validateConfigurationForm($form, new FormState());
    $this->assertSame(['foo'], $form);
    $manualExtractorPlugin->submitConfigurationForm($form, new FormState());
    $this->assertSame(['foo'], $form);

    // Test buildConfigurationForm with options.
    $manualExtractorPlugin = new ManualExtractor(
      [
        'options' => [
          'foo' => [
            'type' => 'string',
          ],
        ],
      ],
      'test_extractor',
      [],
      $this->createMock(UiIconsFinder::class),
    );

    $this->assertInstanceOf(UiIconsExtractorPluginInterface::class, $manualExtractorPlugin);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')->willReturn(['foo' => ['#default_value' => 'bar']]);
    $sub_form_state = $this->createMock(SubformStateInterface::class);
    $sub_form_state->method('getCompleteFormState')->willReturn($form_state);

    $expected = [
      'foo' => [
        '#type' => 'textfield',
        '#title' => 'foo',
        '#description' => '',
        '#size' => 60,
        '#default_value' => [
          '#default_value' => 'bar',
        ],
      ],
    ];
    $this->assertSame($expected, $manualExtractorPlugin->buildConfigurationForm($form, $sub_form_state));
  }

}
