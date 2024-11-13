<?php

declare(strict_types=1);

// cspell:ignore corge quux
namespace Drupal\Tests\ui_icons\Unit\Controller;

@class_alias('Drupal\ui_icons_backport\Plugin\IconPackManagerInterface', 'Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface');
@class_alias('Drupal\ui_icons_backport\IconDefinition', 'Drupal\Core\Theme\Icon\IconDefinition');
@class_alias('Drupal\ui_icons_backport\IconDefinitionInterface', 'Drupal\Core\Theme\Icon\IconDefinitionInterface');
@class_alias('Drupal\Tests\ui_icons_backport\IconTestTrait', 'Drupal\Tests\Core\Theme\Icon\IconTestTrait');

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface;
use Drupal\Tests\Core\Theme\Icon\IconTestTrait;
use Drupal\ui_icons\Controller\IconAutocompleteController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\ui_icons\Controller\IconAutocompleteController
 *
 * @group ui_icons
 */
class IconAutocompleteControllerUnitTest extends TestCase {

  use IconTestTrait;

  /**
   * The container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  private ContainerBuilder $container;

  /**
   * The icon pack manager.
   *
   * @var \Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface
   */
  private IconPackManagerInterface $iconPackManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private RendererInterface $renderer;

  /**
   * The IconAutocompleteController.
   *
   * @var \Drupal\ui_icons\Controller\IconAutocompleteController
   */
  private IconAutocompleteController $iconAutocompleteController;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);

    $this->iconPackManager = $this->createMock(IconPackManagerInterface::class);
    $this->renderer = $this->createMock(RendererInterface::class);
    $this->renderer
      ->method('renderInIsolation')
      ->willReturn('_rendered_');

    $this->iconAutocompleteController = new IconAutocompleteController(
      $this->iconPackManager,
      $this->renderer
    );
  }

  /**
   * Test the _construct method.
   */
  public function testConstructor(): void {
    $iconAutocompleteController = new IconAutocompleteController(
      $this->createMock(IconPackManagerInterface::class),
      $this->createMock(RendererInterface::class)
    );

    $this->assertInstanceOf(IconAutocompleteController::class, $iconAutocompleteController);
  }

  /**
   * Provide data for testHandleSearchIcons.
   *
   * @return \Generator
   *   The test cases.
   */
  public static function handleSearchIconsDataProviderId(): iterable {

    yield 'empty' => [
      'query' => '',
    ];

    yield 'space' => [
      'query' => ' ',
    ];

    yield 'one char' => [
      'query' => 'a',
    ];

    yield 'one char spaces' => [
      'query' => ' a ',
    ];

    yield 'no words' => [
      'query' => '?!%$*+',
    ];

    // Test the id based search.
    yield 'id exact full' => [
      'query' => '_match_:_match_',
      'allowed_icon_pack' => NULL,
      'icons' => [
        'foo:bar',
        '_match_:_match_',
        'baz:corge',
      ],
      'expected' => [
        '_match_:_match_',
      ],
    ];

    yield 'id exact full wrong pack' => [
      'query' => '_match_:_match_',
      'allowed_icon_pack' => ['other'],
      'icons' => [
        'foo:bar',
        '_match_:_match_',
        'baz:corge',
      ],
      'expected' => NULL,
    ];

    yield 'id exact full good pack' => [
      'query' => '_match_pack_:_match_',
      'allowed_icon_pack' => ['_match_pack_'],
      'icons' => [
        'foo:bar',
        '_match_pack_:_match_',
        'baz:corge',
      ],
      'expected' => [
        '_match_pack_:_match_',
      ],
    ];

    yield 'id exact' => [
      'query' => '_match_',
      'allowed_icon_pack' => NULL,
      'icons' => [
        'bar:_match_',
        'baz:corge',
        'foo:_match_',
      ],
      'expected' => [
        'bar:_match_',
        'foo:_match_',
      ],
    ];

    yield 'id exact wrong pack' => [
      'query' => '_match_',
      'allowed_icon_pack' => ['other'],
      'icons' => [
        'bar:_match_',
        'baz:corge',
        'foo:_match_',
      ],
      'expected' => NULL,
    ];

    yield 'id exact good pack' => [
      'query' => '_match_',
      'allowed_icon_pack' => ['_match_pack_'],
      'icons' => [
        'bar:_match_',
        'baz:corge',
        '_match_pack_:_match_',
      ],
      'expected' => [
        '_match_pack_:_match_',
      ],
    ];

    yield 'id exact multiple results' => [
      'query' => '_match_',
      'allowed_icon_pack' => NULL,
      'icons' => [
        'foo:_match_',
        'bar:_match_',
        'bar:bar',
        'baz:_match_',
        'baz:baz',
        'corge:_match_',
      ],
      'expected' => [
        'foo:_match_',
        'bar:_match_',
        'baz:_match_',
        'corge:_match_',
      ],
    ];

    yield 'id exact multiple results wrong pack' => [
      'query' => '_match_',
      'allowed_icon_pack' => ['other'],
      'icons' => [
        'foo:_match_',
        'bar:_match_',
        'bar:bar',
        'baz:_match_',
        'baz:baz',
        'corge:_match_',
      ],
      'expected' => NULL,
    ];

    yield 'id exact multiple results good packs' => [
      'query' => '_match_',
      'allowed_icon_pack' => ['_match_pack_', '_match_pack_2_'],
      'icons' => [
        'bar:_match_',
        'bar:bar',
        '_match_pack_:_match_',
        'baz:_match_',
        'baz:baz',
        '_match_pack_2_:_match_',
      ],
      'expected' => [
        '_match_pack_:_match_',
        '_match_pack_2_:_match_',
      ],
    ];
  }

  /**
   * Provide data for testHandleSearchIcons.
   *
   * @return \Generator
   *   The test cases.
   */
  public static function handleSearchIconsDataProviderWord(): iterable {
    // Test words search.
    yield 'word result' => [
      'query' => 'fo',
      'allowed_icon_pack' => NULL,
      'icons' => [
        'bar:foo',
        'bar:bar',
        'baz: this is foo',
        'baz:baz',
        'corge:bar',
        'corge:foo or what? ',
        'qux:bar',
      ],
      'expected' => [
        'bar:foo',
        'baz: this is foo',
        'corge:foo or what? ',
      ],
    ];

    yield 'words with one match' => [
      'query' => 'other bar',
      'allowed_icon_pack' => NULL,
      'icons' => [
        'foo:_partial_barista_',
        'qux:quux',
        'baz:corge',
        'baz:baz',
      ],
      'expected' => [
        'foo:_partial_barista_',
      ],
    ];

    yield 'words with one match wrong pack' => [
      'query' => 'foo bar',
      'allowed_icon_pack' => ['other'],
      'icons' => [
        'foo:_partial_barista_',
        'qux:quux',
        'baz:corge',
        'baz:baz',
      ],
      'expected' => NULL,
    ];

    yield 'words with one match good pack' => [
      'query' => 'foo bar',
      'allowed_icon_pack' => ['_match_pack_'],
      'icons' => [
        '_match_pack_:_partial_barista_',
        'qux:quux',
        'baz:corge',
        'baz:baz',
      ],
      'expected' => [
        '_match_pack_:_partial_barista_',
      ],
    ];

    yield 'words with multiple match' => [
      'query' => 'foo bar',
      'allowed_icon_pack' => NULL,
      'icons' => [
        'foo:_partial_barista_',
        'qux:quux',
        'baz: some foolish',
        'baz:baz',
      ],
      'expected' => [
        'foo:_partial_barista_',
        'baz: some foolish',
      ],
    ];

    yield 'words with multiple match and one pack' => [
      'query' => 'foo bar',
      'allowed_icon_pack' => ['_match_pack_'],
      'icons' => [
        '_match_pack_:_partial_barista_',
        'qux:quux',
        '_other_match_pack_: some foolish',
        'baz:baz',
      ],
      'expected' => [
        '_match_pack_:_partial_barista_',
      ],
    ];

    yield 'words with multiple match and multiple pack' => [
      'query' => 'foo bar',
      'allowed_icon_pack' => ['_match_pack_', '_other_match_pack'],
      'icons' => [
        '_match_pack_:_partial_barista_',
        'qux:quux',
        '_match_pack_: some foolish',
        'baz:baz',
      ],
      'expected' => [
        '_match_pack_:_partial_barista_',
        '_match_pack_: some foolish',
      ],
    ];

    yield 'words specials chars ignored with multiple match' => [
      'query' => '!foo? !ùµ$::!bar.çà',
      'allowed_icon_pack' => NULL,
      'icons' => [
        'foo:_partial_barista_',
        'qux:quux',
        'baz: some foolish',
        'baz:baz',
      ],
      'expected' => [
        'foo:_partial_barista_',
        'baz: some foolish',
      ],
    ];
  }

  /**
   * Tests the handleSearchIcons method of the IconAutocompleteController.
   *
   * @param string $query
   *   The search query to test.
   * @param array|null $allowed_icon_pack
   *   The limited allowed icon pack to test.
   * @param array $icons
   *   The icons returned by IconPackManager::getIcons().
   * @param array|null $expected
   *   The expected result values.
   *
   * @dataProvider handleSearchIconsDataProviderId
   * @dataProvider handleSearchIconsDataProviderWord
   */
  public function testHandleSearchIcons(string $query, ?array $allowed_icon_pack = NULL, array $icons = [], ?array $expected = NULL): void {

    $this->preparePackManagerMock($icons, $allowed_icon_pack);
    $request = $this->prepareRequest($query, $allowed_icon_pack);

    $search = $this->iconAutocompleteController->handleSearchIcons($request);
    $result = json_decode($search->getContent(), TRUE);

    if (NULL === $expected) {
      $this->assertEmpty($result);
      return;
    }

    $this->assertCount(count($expected), $result);

    foreach ($expected as $index => $expected_icon_id) {
      $this->assertEquals($expected_icon_id, $result[$index]['value']);
      $this->assertArrayHasKey('label', $result[$index]);
    }
  }

  /**
   * Helper to mock the iconPackManager getIcon() and getIcons() method.
   *
   * @param array $icons
   *   The icons returned by IconPackManager::getIcons().
   * @param array|null $allowed_icon_pack
   *   The limited allowed icon pack to test.
   */
  private function preparePackManagerMock(array $icons = [], ?array $allowed_icon_pack = NULL): void {
    if (NULL !== $allowed_icon_pack) {
      foreach ($icons as $key => $icon_full_id) {
        [$pack_id] = explode(IconDefinition::ICON_SEPARATOR, $icon_full_id);
        if (!in_array($pack_id, $allowed_icon_pack)) {
          unset($icons[$key]);
        }
      }
    }

    $this->iconPackManager
      ->method('getIcons')
      ->with($allowed_icon_pack)
      ->willReturn(array_flip($icons));

    $icons_returned_map = [];
    foreach ($icons as $icon_full_id) {
      [$pack_id, $icon_id] = explode(IconDefinition::ICON_SEPARATOR, $icon_full_id);
      $icons_returned_map[] = [
        $icon_full_id,
        $this->createTestIcon([
          'pack_id' => $pack_id,
          'icon_id' => $icon_id,
        ]),
      ];
    }

    $this->iconPackManager
      ->method('getIcon')
      ->willReturnMap($icons_returned_map);
  }

  /**
   * Helper to prepare the request object.
   *
   * @param string $query
   *   The search query to test.
   * @param array|null $allowed_icon_pack
   *   The limited allowed icon pack to test.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request object.
   */
  public function prepareRequest(string $query, ?array $allowed_icon_pack = NULL): Request {
    $request['q'] = $query;
    if (NULL !== $allowed_icon_pack) {
      $request['allowed_icon_pack'] = implode('+', $allowed_icon_pack);
    }

    return new Request($request);
  }

}
