<?php

declare(strict_types=1);

namespace Drupal\ui_icons;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Core\Theme\Icon\IconDefinitionInterface;
use Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handle an Icon search.
 *
 * Main entrypoint to search and filter icons based on name or pack.
 */
class IconSearch implements ContainerInjectionInterface {

  public const SEARCH_MIN_LENGTH = 2;
  public const SEARCH_MAX_RESULT = 20;

  public function __construct(
    private readonly IconPackManagerInterface $pluginManagerIconPack,
    private readonly RendererInterface $renderer,
    private CacheBackendInterface $cache,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.icon_pack'),
      $container->get('renderer'),
      $container->get('cache.default'),
    );
  }

  /**
   * Find an icon based on search string.
   *
   * The search try to be fuzzy on words with a priority:
   *  - Words in order
   *  - Words in any order
   *  - Any parts of words.
   *
   * @param string $query
   *   The query to search for.
   * @param array $allowed_icon_pack
   *   Restrict to an icon pack list.
   * @param int|null $max_result
   *   Maximum result to return, all icons if null.
   * @param callable|null $result_callback
   *   A callable to process each result.
   *
   * @return array
   *   The icons matching the search.
   */
  public function search(
    string $query,
    array $allowed_icon_pack = [],
    ?int $max_result = self::SEARCH_MAX_RESULT,
    ?callable $result_callback = NULL,
  ): array {
    if (empty($query) || mb_strlen($query) < self::SEARCH_MIN_LENGTH) {
      return [];
    }

    $icons = $this->pluginManagerIconPack->getIcons($allowed_icon_pack);
    if (empty($icons)) {
      return [];
    }

    // If the search is an exact icon full id let return faster.
    if (isset($icons[$query])) {
      return [$this->createResultEntry($query, $result_callback)];
    }

    $cache_data = [];
    if ($cache = $this->cache->get('icon_search')) {
      $cache_data = $cache->data;
      if (isset($cache_data[$query])) {
        return $cache_data[$query];
      }
    }

    // Prepare multi words search by removing unwanted characters.
    $words = preg_split('/\s+/', trim(preg_replace('/[^ \w-]/', ' ', mb_strtolower($query))));
    if (empty($words)) {
      return [];
    }

    // Prepare pattern for exact and any order matches.
    $pattern = '/\b(' . implode('|', array_map(function ($word) {
      return preg_quote($word, '/');
    }, $words)) . ')\b/i';

    $matches = $matched_ids = [];
    $icon_list = array_keys($icons);
    foreach ($icon_list as $icon_full_id) {
      $icon_data = IconDefinition::getIconDataFromId($icon_full_id);
      if ($allowed_icon_pack && !in_array($icon_data['pack_id'], $allowed_icon_pack)) {
        continue;
      }

      // Priority search is on id and then pack for order.
      $icon_search = $icon_data['icon_id'] . ' ' . $icon_data['pack_id'];

      // Check for exact order or any order matches.
      if (preg_match($pattern, $icon_search)) {
        $entry = $this->createResultEntry($icon_full_id, $result_callback);
        if ($entry && !isset($matched_ids[$icon_full_id])) {
          $matches[] = $entry;
          $matched_ids[$icon_full_id] = TRUE;
        }
      }
      else {
        // Fallback to search partial string.
        foreach ($words as $word) {
          if (str_contains($icon_search, $word)) {
            $entry = $this->createResultEntry($icon_full_id, $result_callback);
            if ($entry && !isset($matched_ids[$icon_full_id])) {
              $matches[] = $entry;
              $matched_ids[$icon_full_id] = TRUE;
            }
            break;
          }
        }
      }

      if ($max_result && count($matches) >= $max_result) {
        break;
      }
    }

    $cache_data[$query] = $matches;
    $this->cache->set(
      'icon_search',
      $cache_data,
      CacheBackendInterface::CACHE_PERMANENT,
      ['icon_pack_plugin', 'icon_pack_collector']
    );

    return $matches;
  }

  /**
   * Create icon result.
   *
   * @param string $icon_full_id
   *   The icon full id.
   * @param callable|null $callback
   *   A callable to process the result.
   *
   * @return string|array|null
   *   The icon result passed through the callback.
   */
  private function createResultEntry(string $icon_full_id, ?callable $callback = NULL): mixed {
    if (NULL === $callback) {
      return $icon_full_id;
    }

    $icon = $this->pluginManagerIconPack->getIcon($icon_full_id);
    if (!$icon instanceof IconDefinitionInterface) {
      return NULL;
    }

    $icon_renderable = IconPreview::getPreview($icon, ['size' => 24]);
    $rendered = $this->renderer->renderInIsolation($icon_renderable);

    return call_user_func($callback, $icon, $rendered);
  }

}
