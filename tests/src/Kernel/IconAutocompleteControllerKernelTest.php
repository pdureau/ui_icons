<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons\Kernel;

@class_alias('Drupal\ui_icons_backport\Plugin\IconPackManagerInterface', 'Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface');

use Drupal\KernelTests\KernelTestBase;
use Drupal\ui_icons\Controller\IconAutocompleteController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\ui_icons\Controller\IconAutocompleteController
 *
 * @group icon
 */
class IconAutocompleteControllerKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'ui_icons',
    'ui_icons_backport',
    'ui_icons_test',
  ];

  /**
   * The IconAutocompleteController instance.
   *
   * @var \Drupal\ui_icons\Controller\IconAutocompleteController
   */
  private IconAutocompleteController $iconAutocompleteController;

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

    $pluginManagerIconPack = $this->container->get('plugin.manager.icon_pack');
    $renderer = $this->container->get('renderer');

    $this->iconAutocompleteController = new IconAutocompleteController(
      $pluginManagerIconPack,
      $renderer,
    );
  }

  /**
   * Tests the handleSearchIcons method of the IconAutocompleteController.
   */
  public function testHandleSearchIconsResultLabel(): void {
    $search = $this->iconAutocompleteController->handleSearchIcons(new Request(['q' => 'test_minimal:foo']));
    $result = json_decode($search->getContent(), TRUE);
    $result_label = str_replace("\n", "", $result[0]['label']);
    $expected_label = '<span class="ui-menu-icon">  <img class="icon icon-preview" src="/modules/custom/ui_icons/tests/modules/ui_icons_test/icons/flat/foo.png" title="test_minimal:foo" alt="foo" width="24" height="24"></span> Foo (test_minimal)';

    $this->assertEquals($expected_label, $result_label);
  }

}
