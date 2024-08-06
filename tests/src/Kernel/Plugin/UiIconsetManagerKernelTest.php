<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons\Kernel\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\ui_icons\Exception\IconsetConfigErrorException;
use Drupal\ui_icons\IconDefinitionInterface;
use Drupal\ui_icons\Plugin\UiIconsetManager;
use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;

/**
 * Tests the UiIconsetManager class.
 *
 * @group ui_icons
 */
class UiIconsetManagerKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'ui_icons',
    'ui_icons_test',
  ];

  /**
   * The UiIconsetManager instance.
   *
   * @var \Drupal\ui_icons\Plugin\UiIconsetManagerInterface
   */
  private UiIconsetManagerInterface $uiIconsetManager;

  /**
   * The App root instance.
   *
   * @var string
   */
  private string $appRoot;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $module_handler = $this->container->get('module_handler');
    $theme_handler = $this->container->get('theme_handler');
    $cache_backend = $this->container->get('cache.default');
    $ui_icons_extractor_plugin_manager = $this->container->get('plugin.manager.ui_icons_extractor');
    $this->appRoot = $this->container->getParameter('app.root');

    $this->uiIconsetManager = new UiIconsetManager(
      $module_handler,
      $theme_handler,
      $cache_backend,
      $ui_icons_extractor_plugin_manager,
      $this->appRoot,
    );
  }

  /**
   * Test the _construct method.
   */
  public function testConstructor(): void {
    $this->assertInstanceOf(UiIconsetManager::class, $this->uiIconsetManager);
  }

  /**
   * Test the getIcons method.
   */
  public function testGetIcons(): void {
    $icons = $this->uiIconsetManager->getIcons();
    $this->assertIsArray($icons);

    $this->assertArrayHasKey('test_local_files:local__9.0_black', $icons);
  }

  /**
   * Test the getIcon method.
   */
  public function testGetIcon(): void {
    $icon = $this->uiIconsetManager->getIcon('test_local_files:local__9.0_black');
    $this->assertInstanceOf(IconDefinitionInterface::class, $icon);

    $icon = $this->uiIconsetManager->getIcon('test_local_files:_do_not_exist_');
    $this->assertNull($icon);
  }

  /**
   * Test the listIconsetOptions method.
   */
  public function testListIconsetOptions(): void {
    $actual = $this->uiIconsetManager->listIconsetOptions();
    $expected = [
      'test_local_files' => 'Local files',
      'test_local_svg' => 'SVG manual',
      'test_local_svg_sprite' => 'Small sprite',
      'test_no_icons' => 'No Icons',
    ];
    $this->assertSame($expected, $actual);
  }

  /**
   * Test the listIconsetWithDescriptionOption method.
   */
  public function testListIconsetWithDescriptionOptions(): void {
    $actual = $this->uiIconsetManager->listIconsetWithDescriptionOptions();
    $expected = [
      'test_local_files' => 'Local files - Local files relative available.',
      'test_local_svg' => 'SVG manual - Local svg files.',
      'test_local_svg_sprite' => 'Small sprite - Local svg sprite file.',
      'test_no_icons' => 'No Icons',
    ];
    $this->assertSame($expected, $actual);
  }

  /**
   * Test the listOptions method.
   */
  public function testListOptions(): void {
    $actual = $this->uiIconsetManager->listOptions();
    $this->assertCount(20, $actual);

    $actual = $this->uiIconsetManager->listOptions(['test_local_svg']);
    $this->assertCount(7, $actual);

    $actual = $this->uiIconsetManager->listOptions(['test_no_icons']);
    $this->assertCount(0, $actual);

    $actual = $this->uiIconsetManager->listOptions(['do_not_exist']);
    $this->assertCount(0, $actual);
  }

  /**
   * Test the getExtractorAllFormDefaults method.
   */
  public function testGetExtractorAllFormDefaults(): void {
    $actual = $this->uiIconsetManager->getExtractorAllFormDefaults();
    $expected = [
      'test_local_files' => [
        'foo' => 50,
        'bar' => 'baz',
      ],
    ];
    $this->assertSame($expected, $actual);
  }

  /**
   * Test the getExtractorFormDefault method.
   */
  public function testGetExtractorFormDefaults(): void {
    $actual = $this->uiIconsetManager->getExtractorFormDefaults('test_local_files');
    $expected = [
      'foo' => 50,
      'bar' => 'baz',
    ];
    $this->assertSame($expected, $actual);

    $actual = $this->uiIconsetManager->getExtractorFormDefaults('test_no_icons');
    $this->assertSame([], $actual);
  }

  /**
   * Test the getExtractorPluginForms method.
   */
  public function testGetExtractorPluginForms(): void {
    $form_state = $this->createMock(FormStateInterface::class);

    $form = [
      'test_local_files' => ['path' => []],
      'test_local_svg' => ['svg' => []],
    ];

    $this->uiIconsetManager->getExtractorPluginForms($form, $form_state);
    $this->assertSame('Local files', $form['test_local_files']['#title']);
    $this->assertSame('SVG manual', $form['test_local_svg']['#title']);

    $form = [
      'test_local_files' => ['path' => []],
      'test_local_svg' => ['svg' => []],

    ];
    $default_settings = [
      'test_local_files' => [
        'path' => [],
      ],
    ];
    $original_form = $form;
    $this->uiIconsetManager->getExtractorPluginForms($form, $form_state, $default_settings, ['foo' => 'bar']);
    $this->assertSame($original_form, $form);
  }

  /**
   * Test the processDefinition method.
   */
  public function testProcessDefinition(): void {
    $definition = [
      'id' => 'foo',
      'label' => 'Foo',
      'provider' => 'ui_icons_test',
      'extractor' => 'bar',
      'config' => [],
    ];

    $this->uiIconsetManager->processDefinition($definition, 'foo');

    $expected = [
      'id' => 'foo',
      'label' => 'Foo',
      'provider' => 'ui_icons_test',
      'extractor' => 'bar',
      'config' => [],
      '_path_info' => [
        'drupal_root' => $this->appRoot,
        'absolute_path' => $this->appRoot . '/modules/custom/ui_icons/tests/modules/ui_icons_test',
        'relative_path' => 'modules/custom/ui_icons/tests/modules/ui_icons_test',
      ],
      'iconset_id' => 'foo',
      'iconset_label' => 'Foo',
    ];

    $this->assertEquals($expected, $definition);
  }

  /**
   * Test the processDefinition method.
   */
  public function testProcessDefinitionExceptionName(): void {
    $definition = [];
    $this->expectException(IconsetConfigErrorException::class);
    $this->expectExceptionMessage('Invalid Iconset id, name must contain only lowercase letters, numbers, and underscores.');
    $this->uiIconsetManager->processDefinition($definition, '$ Not valid !*');
  }

  /**
   * Test the processDefinition method.
   */
  public function testProcessDefinitionExceptionExtractor(): void {
    $definition = [
      'id' => 'foo',
      'label' => 'Foo',
      'provider' => 'ui_icons_test',
    ];
    $this->expectException(IconsetConfigErrorException::class);
    $this->expectExceptionMessage('Missing `extractor:` key in your definition!');
    $this->uiIconsetManager->processDefinition($definition, 'foo');
  }

  /**
   * Test the processDefinition method.
   */
  public function testProcessDefinitionExceptionConfig(): void {
    $definition = [
      'id' => 'foo',
      'label' => 'Foo',
      'provider' => 'ui_icons_test',
      'extractor' => 'bar',
    ];
    $this->expectException(IconsetConfigErrorException::class);
    $this->expectExceptionMessage('Missing `config:` key in your definition extractor!');
    $this->uiIconsetManager->processDefinition($definition, 'foo');
  }

}
