<?php

declare(strict_types=1);

namespace Drupal\ui_icons\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\ui_icons\IconDefinitionInterface;
use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for UI Icons routes.
 *
 * @todo provide the icon rendered in the result or after selection
 */
class UiIconsAutocompleteController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly UiIconsetManagerInterface $pluginManagerUiIconset,
    private readonly RendererInterface $renderer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.ui_iconset'),
      $container->get('renderer'),
    );
  }

  /**
   * Menu callback for UI Icons icon request.
   *
   * This function inspects the 'q' query parameter for the icon ID.
   * Optional width and height parameters can be set.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response containing the rendered icon.
   */
  public function handleRenderIcon(Request $request): Response {
    $icon_id = $request->query->get('q', NULL);
    if (empty($icon_id)) {
      return new Response();
    }

    $width = $request->query->get('width', 32);
    $height = $request->query->get('height', 32);

    $icon = $this->pluginManagerUiIconset->getIcon($icon_id);
    if (!$icon) {
      return new Response();
    }
    $icon_renderable = $icon->getRenderable(['width' => $width, 'height' => $height]);
    $renderable = $this->renderer->renderInIsolation($icon_renderable);

    $response = new Response((string) $renderable, 200);
    return $response;
  }

  /**
   * Menu callback for UI Icons autocompletion.
   *
   * This function inspects the 'q' query parameter for the string to use to
   * search for icons, and allowed_iconset if set.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for Icons.
   */
  public function handleSearchIcons(Request $request): JsonResponse {
    $query = $request->query->get('q', '');
    if (empty(trim($query))) {
      return new JsonResponse();
    }

    // phpcs:disable Drupal.Commenting.InlineComment.SpacingBefore
    // @todo Drupal core autocomplete is set to minLength 1, we cannot yet
    // have a minimum size unless implementing our own autocomplete wrapper.
    // @see web/core/misc/autocomplete.js
    // if (mb_strlen($query) < 3) {
    //   return new JsonResponse();
    // }
    // phpcs:enable Drupal.Commenting.InlineComment.SpacingBefore
    $query = strtolower($query);
    $query = preg_replace('/[^ \w-]/', '', $query);

    $allowed_iconset = $request->query->get('allowed_iconset', NULL);
    if ($allowed_iconset) {
      $allowed_iconset = explode('+', $allowed_iconset);
    }

    $max_result = $request->query->get('max_result', 10);

    $icons = $this->pluginManagerUiIconset->getIcons();

    if (empty($icons)) {
      return new JsonResponse();
    }

    $result = $this->searchIcon($icons, $query, $max_result, $allowed_iconset);

    return new JsonResponse($result);
  }

  /**
   * Find an icon based on search string.
   *
   * @param \Drupal\ui_icons\IconDefinitionInterface[] $icons
   *   The list of icons definitions.
   * @param string $search
   *   The keywords to search.
   * @param int $max_result
   *   Maximum result to show, default 10.
   * @param array $allowed_iconset
   *   Restrict to an iconset list.
   *
   * @return array
   *   The icons matching the search.
   */
  private function searchIcon(array $icons, string $search, int $max_result = 10, ?array $allowed_iconset = NULL): array {
    $result = [];

    $search_pattern = '/' . preg_quote($search, '/') . '/i';

    foreach ($icons as $icon_id => $icon) {
      if ($max_result === 0) {
        break;
      }

      if ($allowed_iconset) {
        $iconset_match = FALSE;
        foreach ($allowed_iconset as $iconset) {
          if (str_starts_with($icon_id, $iconset . ':')) {
            $iconset_match = TRUE;
            break;
          }
        }
        if (!$iconset_match) {
          continue;
        }
      }

      // @todo better explode
      $icon_name = $icon->getName() . ' ' . explode(':', $icon_id)[1];

      if (preg_match($search_pattern, $icon_name)) {
        $result[] = $this->createResultEntry($icon_id, $icon);
        $max_result--;
      }
    }

    return $result;
  }

  /**
   * Create icon result.
   *
   * @param string $icon_id
   *   The full icon id.
   * @param \Drupal\ui_icons\IconDefinitionInterface $icon
   *   The icon definition.
   *
   * @return array
   *   The icon result with keys 'value' and 'label'.
   */
  private function createResultEntry(string $icon_id, IconDefinitionInterface $icon): array {
    $label = sprintf('%s (%s)', $icon->getName(), $icon->getIconsetLabel());
    $icon_renderable = $icon->getRenderable(['width' => 20, 'height' => 20]);
    $renderable = $this->renderer->renderInIsolation($icon_renderable);
    $param = ['@icon' => $renderable, '@name' => $label];
    $label = new FormattableMarkup('<span class="ui-menu-icon">@icon</span> @name', $param);

    return ['value' => $icon_id, 'label' => $label];
  }

}
