<?php

declare(strict_types=1);

namespace Drupal\ui_icons\Controller;

@class_alias('Drupal\ui_icons_backport\Plugin\IconPackManagerInterface', 'Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface');

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Core\Theme\Icon\IconDefinitionInterface;
use Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface;
use Drupal\ui_icons\IconPreview;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for UI Icons routes.
 *
 * @todo provide the icon rendered in the result or after selection
 */
class IconAutocompleteController extends ControllerBase {

  private const SEARCH_MIN_LENGTH = 2;
  private const SEARCH_MAX_RESULT = 20;

  public function __construct(
    private readonly IconPackManagerInterface $pluginManagerIconPack,
    private readonly RendererInterface $renderer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.icon_pack'),
      $container->get('renderer'),
    );
  }

  /**
   * Menu callback for UI Icons autocompletion.
   *
   * This function inspects the 'q' query parameter for the string to use to
   * search for icons, and allowed_icon_pack if set.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for Icons.
   *
   * @todo cache search for faster lookup.
   */
  public function handleSearchIcons(Request $request): JsonResponse {
    $query = trim((string) $request->query->get('q', ''));

    // @todo global match length with autocomplete in
    // web/modules/ui_icons/js/icon.autocomplete.js
    if (empty($query) || mb_strlen($query) < self::SEARCH_MIN_LENGTH) {
      return new JsonResponse([]);
    }

    $allowed_icon_pack = NULL;
    if ($request->query->get('allowed_icon_pack', NULL)) {
      $allowed_icon_pack = explode('+', (string) $request->query->get('allowed_icon_pack', ''));
    }

    $icons = $this->pluginManagerIconPack->getIcons($allowed_icon_pack);
    if (empty($icons)) {
      return new JsonResponse([]);
    }

    // If the search is an exact icon full id let return faster.
    if (isset($icons[$query])) {
      return new JsonResponse([$this->createResultEntry($query)]);
    }

    // Prepare multi words search by removing unwanted characters.
    $words_search = preg_split('/\s+/', trim(preg_replace('/[^ \w-]/', ' ', $query)));
    if (empty($words_search)) {
      return new JsonResponse([]);
    }

    $max_result = (int) $request->query->get('max_result', self::SEARCH_MAX_RESULT);
    $result = $this->searchIcon($icons, $words_search, $max_result, $allowed_icon_pack);

    return new JsonResponse($result);
  }

  /**
   * Find an icon based on search string.
   *
   * The search try to be fuzzy on words with a priority:
   *  - Words in order
   *  - Words in any order
   *  - Any parts of words.
   *
   * @param array $icons
   *   The list of icon ids.
   * @param array $words
   *   The keywords to search.
   * @param int $max_result
   *   Maximum result to show.
   * @param array|null $allowed_icon_pack
   *   Restrict to an icon pack list.
   *
   * @return array
   *   The icons matching the search.
   */
  private function searchIcon(array $icons, array $words, int $max_result, ?array $allowed_icon_pack = NULL): array {
    // Prepare pattern for exact and any order matches.
    $pattern = '/\b(' . implode('|', array_map(function ($word) {
      return preg_quote($word, '/');
    }, $words)) . ')\b/i';

    $matches = $matched_ids = [];
    $icon_list = array_keys($icons);
    foreach ($icon_list as $icon_full_id) {
      [$pack_id, $icon_id] = explode(IconDefinition::ICON_SEPARATOR, $icon_full_id);
      if ($allowed_icon_pack && !in_array($pack_id, $allowed_icon_pack)) {
        continue;
      }

      // Priority search is on id and then pack for order.
      $icon_search = $icon_id . ' ' . $pack_id;

      // Check for exact order or any order matches.
      if (preg_match($pattern, $icon_search)) {
        $entry = $this->createResultEntry($icon_full_id);
        if ($entry && !isset($matched_ids[$icon_full_id])) {
          $matches[] = $entry;
          $matched_ids[$icon_full_id] = TRUE;
        }
      }
      else {
        // Fallback to search partial string.
        foreach ($words as $word) {
          if (str_contains($icon_search, $word)) {
            $entry = $this->createResultEntry($icon_full_id);
            if ($entry && !isset($matched_ids[$icon_full_id])) {
              $matches[] = $entry;
              $matched_ids[$icon_full_id] = TRUE;
            }
            break;
          }
        }
      }

      if (count($matches) >= $max_result) {
        break;
      }
    }

    return $matches;
  }

  /**
   * Create icon result.
   *
   * @param string $icon_full_id
   *   The icon full id.
   *
   * @return array|null
   *   The icon result with keys 'value' and 'label'.
   */
  private function createResultEntry(string $icon_full_id): ?array {
    $icon = $this->pluginManagerIconPack->getIcon($icon_full_id);
    if (!$icon instanceof IconDefinitionInterface) {
      return NULL;
    }

    $icon_renderable = IconPreview::getPreview($icon, ['size' => 24]);
    $renderable = $this->renderer->renderInIsolation($icon_renderable);

    $label = sprintf('%s (%s)', $icon->getLabel(), $icon->getPackLabel() ?? $icon->getPackId());
    $param = ['@icon' => $renderable, '@name' => $label];
    $label = new FormattableMarkup('<span class="ui-menu-icon">@icon</span> @name', $param);

    return ['value' => $icon->getId(), 'label' => $label];
  }

}
