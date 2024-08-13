<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons\Unit\Element;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\ui_icons\Unit\IconUnitTestCase;
use Drupal\ui_icons\Controller\IconAutocompleteController;
use Drupal\ui_icons\Plugin\IconPackManagerInterface;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests IconAutocompleteController Controller class.
 *
 * @group ui_icons
 *
 * @cspell:ignore tri OME
 */
class IconAutocompleteControllerTest extends IconUnitTestCase {

  /**
   * The container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  private ContainerBuilder $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);
  }

  /**
   * Test the _construct method.
   */
  public function testConstructor(): void {
    $iconPackManager = $this->createMock(IconPackManagerInterface::class);
    $renderer = $this->createMock(RendererInterface::class);
    $iconAutocompleteController = new IconAutocompleteController($iconPackManager, $renderer);

    $this->assertInstanceOf('Drupal\ui_icons\Controller\IconAutocompleteController', $iconAutocompleteController);
  }

  /**
   * Test the handleRenderIcon method.
   *
   * @param string|null $iconId
   *   The ID of the icon to be rendered. Can be null.
   * @param bool $hasResult
   *   Should have icon result.
   * @param array $queryParams
   *   The query parameters to be passed in the request.
   * @param array|null $iconData
   *   The data of the icon to be rendered. Can be null.
   * @param string $expectedContent
   *   The expected content of the response.
   * @param int $expectedStatusCode
   *   The expected status code of the response.
   *
   * @dataProvider handleRenderIconDataProvider
   */
  public function testHandleRenderIcon(?string $iconId, bool $hasResult, array $queryParams, ?array $iconData, string $expectedContent, int $expectedStatusCode): void {
    $prophecy = $this->prophesize(IconPackManagerInterface::class);
    if ($hasResult) {
      $icon = $this->createMockIcon($iconData);
      $prophecy->getIcon($iconId)->willReturn($icon);
    }
    else {
      $prophecy->getIcon(Argument::any())->willReturn(NULL);
    }
    $iconPackManager = $prophecy->reveal();
    $this->container->set('plugin.manager.ui_icons_pack', $iconPackManager);

    $prophecy = $this->prophesize(RendererInterface::class);
    $prophecy->renderInIsolation(Argument::any())->willReturn($expectedContent);
    $renderer = $prophecy->reveal();
    $this->container->set('renderer', $renderer);

    $iconAutocompleteController = new IconAutocompleteController($iconPackManager, $renderer);

    $request = new Request($queryParams);
    $actual = $iconAutocompleteController->handleRenderIcon($request);

    $expected = new Response($expectedContent, $expectedStatusCode);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Provide data for testHandleRenderIcon.
   *
   * @return array
   *   Test data.
   */
  public static function handleRenderIconDataProvider(): array {
    return [
      'empty query' => [
        'iconId' => NULL,
        'hasResult' => FALSE,
        'queryParams' => ['q' => ''],
        'iconData' => NULL,
        'expectedContent' => '',
        'expectedStatusCode' => 200,
      ],
      'valid icon query' => [
        'iconId' => 'foo:bar',
        'hasResult' => TRUE,
        'queryParams' => ['q' => 'foo:bar', 'width' => 100, 'height' => 100],
        'iconData' => ['icon_pack_id' => 'foo', 'icon_id' => 'bar', 'width' => 100, 'height' => 100],
        'expectedContent' => '_rendered_',
        'expectedStatusCode' => 200,
      ],
      'valid icon query with custom dimensions' => [
        'iconId' => 'foo:bar',
        'hasResult' => TRUE,
        'queryParams' => ['q' => 'foo:bar', 'width' => 200, 'height' => 200],
        'iconData' => ['icon_pack_id' => 'foo', 'icon_id' => 'bar', 'width' => 200, 'height' => 200],
        'expectedContent' => '_rendered_',
        'expectedStatusCode' => 200,
      ],
      'invalid query' => [
        'iconId' => 'foo:bar',
        'hasResult' => FALSE,
        'queryParams' => ['q' => 'baz:foo', 'width' => 100, 'height' => 100],
        'iconData' => NULL,
        'expectedContent' => '',
        'expectedStatusCode' => 200,
      ],
    ];
  }

  /**
   * Tests the handleSearchIcons method of the IconAutocompleteController.
   *
   * @param array $iconsData
   *   The array of icons data to be created.
   * @param array $queryParams
   *   The query parameters to be passed to the Request object.
   * @param array|null $expectedData
   *   The expected data to be returned in the JsonResponse.
   *
   * @dataProvider handleSearchIconsDataProvider
   */
  public function testHandleSearchIcons(array $iconsData, array $queryParams, ?array $expectedData): void {
    $prophecy = $this->prophesize(IconPackManagerInterface::class);

    $icons = [];
    foreach ($iconsData as $iconId => $iconData) {
      $icons[$iconId] = $this->createIcon($iconData);
    }
    $prophecy->getIcons()->willReturn($icons);

    $iconPackManager = $prophecy->reveal();
    $this->container->set('plugin.manager.ui_icons_pack', $iconPackManager);

    $prophecy = $this->prophesize(RendererInterface::class);
    $prophecy->renderInIsolation(Argument::any())->willReturn('_rendered_');
    $renderer = $prophecy->reveal();
    $this->container->set('renderer', $renderer);

    $iconAutocompleteController = new IconAutocompleteController($iconPackManager, $renderer);
    $request = new Request($queryParams);
    $actual = $iconAutocompleteController->handleSearchIcons($request);

    if (NULL === $expectedData) {
      $expected = new JsonResponse();
    }
    else {
      $expected = new JsonResponse($expectedData);
    }
    $this->assertEquals($expected->getContent(), $actual->getContent());
  }

  /**
   * Provide data for testHandleSearchIcons.
   *
   * @return array
   *   Test data.
   */
  public static function handleSearchIconsDataProvider(): array {
    return [
      'query icon id' => [
        'iconsData' => self::createIconData(),
        'queryParams' => ['q' => 'bar'],
        'expectedData' => [
          self::createIconResultData(),
        ],
      ],
      'query foo with allowed_icon_pack include icon' => [
        'iconsData' => self::createIconData(),
        'queryParams' => ['q' => 'bar', 'allowed_icon_pack' => 'foo+bar'],
        'expectedData' => [
          self::createIconResultData(),
        ],
      ],
      'query foo with allowed_icon_pack do not include icon' => [
        'iconsData' => self::createIconData(),
        'queryParams' => ['q' => 'fo', 'allowed_icon_pack' => 'qux+bar'],
        'expectedData' => [],
      ],
      'query foo with max_result 2 for 3 valid icons' => [
        'iconsData' => self::createIconData('foo', 'foo-qux') + self::createIconData('quux', 'qux-foo') + self::createIconData('corge', 'baz-foo'),
        'queryParams' => ['q' => 'foo', 'max_result' => 2],
        'expectedData' => [
          self::createIconResultData('foo', 'foo-qux'),
          self::createIconResultData('quux', 'qux-foo'),
        ],
      ],
      'query string part name' => [
        'iconsData' => self::createIconData(NULL, 'some-name-string'),
        'queryParams' => ['q' => 'tri'],
        'expectedData' => [
          self::createIconResultData(NULL, 'some-name-string'),
        ],
      ],
      'query string part name with non ascii chars' => [
        'iconsData' => self::createIconData(NULL, 'Some Name String'),
        'queryParams' => ['q' => '%!?OME*$$'],
        'expectedData' => [
          self::createIconResultData(NULL, 'Some Name String'),
        ],
      ],
      'query string icon_id' => [
        'iconsData' => self::createIconData(NULL, 'my_icon_id', 'Some name'),
        'queryParams' => ['q' => 'icon'],
        'expectedData' => [
          self::createIconResultData(NULL, 'my_icon_id', 'Some name'),
        ],
      ],
      'query non ascii letter' => [
        'iconsData' => self::createIconData('%2$*', 'à(5çè', '?:!!/"&', ']}=(-_ù,'),
        'queryParams' => ['q' => '!!'],
        'expectedData' => [
          self::createIconResultData('%2$*', 'à(5çè', '?:!!/"&', ']}=(-_ù,'),
        ],
      ],
      'query icon pack no result' => [
        'iconsData' => self::createIconData('my_icon_pack'),
        'queryParams' => ['q' => 'icon_pack'],
        'expectedData' => [],
      ],
      'query empty' => [
        'iconsData' => self::createIconData(),
        'queryParams' => ['q' => ''],
        'expectedData' => NULL,
      ],
      'query space' => [
        'iconsData' => self::createIconData(),
        'queryParams' => ['q' => ' '],
        'expectedData' => NULL,
      ],
      'query foo with empty icons' => [
        'iconsData' => [],
        'queryParams' => ['q' => 'foo', 'allowed_icon_pack' => 'foo+bar'],
        'expectedData' => NULL,
      ],
    ];
  }

}
