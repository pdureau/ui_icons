<?php

declare(strict_types=1);

namespace Drupal\ui_icons_library\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface;
use Drupal\Core\Url;
use Drupal\ui_icons\IconPreview;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for UI Icons routes.
 */
class LibraryIndex extends ControllerBase {

  private const PREVIEW_ICON_NUM = 32;
  private const PREVIEW_ICON_SIZE = 40;

  public function __construct(
    private readonly IconPackManagerInterface $pluginManagerIconPack,
    private SharedTempStoreFactory $tempStoreFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.icon_pack'),
      $container->get('tempstore.shared'),
    );
  }

  /**
   * Index of Pack list.
   *
   * @return array
   *   Render array of packs.
   */
  public function index(): array {
    $temp_store = $this->tempStoreFactory->get('icon_library');
    $show_disable = $temp_store->get('disabled') ?? 'off';

    $link = $this->t('hide disabled pack');
    $url = Url::fromRoute('ui_icons_library.mode')->toString();
    if ('off' === $show_disable) {
      $link = $this->t('show disabled pack');
      $url = Url::fromRoute('ui_icons_library.mode', ['mode' => 'on'])->toString();
    }

    $build = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => ['class' => ['description']],
      '#value' => $this->t(
        'List of Icon packs available (<a href="@url">@link</a>).',
        ['@url' => $url, '@link' => $link]
      ),
    ];

    $build['grid'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['card_grid']],
    ];

    $icon_pack = $this->pluginManagerIconPack->getDefinitions();
    $icon_preview_ids = [];
    foreach ($icon_pack as $pack_id => $pack_definition) {

      if ('off' === $show_disable) {
        if (isset($pack_definition['enabled']) && FALSE === $pack_definition['enabled']) {
          continue;
        }
      }

      $build['grid'][$pack_id] = [
        '#type' => 'component',
        '#component' => 'ui_icons_library:icon_pack_card',
        '#props' => [
          'label' => $pack_definition['label'] ?? $pack_id,
          'description' => $pack_definition['description'] ?? $pack_id,
          'version' => $pack_definition['version'] ?? '',
          'enabled' => $pack_definition['enabled'] ?? TRUE,
          'link' => Url::fromRoute('ui_icons_library.pack', ['pack_id' => $pack_id]),
          'license_name' => $pack_definition['license']['name'] ?? '',
          'license_url' => $pack_definition['license']['url'] ?? '',
        ],
      ];

      $icons = $this->pluginManagerIconPack->getIcons([$pack_id]);

      if (empty($icons)) {
        continue;
      }

      $build['grid'][$pack_id]['#props']['total'] = count($icons);

      if (count($icons) > self::PREVIEW_ICON_NUM) {
        $rand_keys = array_rand($icons, self::PREVIEW_ICON_NUM);
        $icons = array_intersect_key($icons, array_flip($rand_keys));
      }

      $icon_preview = [];
      $icon_full_ids = array_keys($icons);
      $icon_preview_ids = array_merge($icon_full_ids, $icon_preview_ids);
      foreach ($icon_full_ids as $icon_full_id) {
        $icon_preview[] = [
          '#type' => 'html_tag',
          '#tag' => 'img',
          '#attributes' => [
            'src' => IconPreview::SPINNER_ICON,
            'title' => $icon_full_id,
            'data-icon-id' => $icon_full_id,
            'class' => [
              'icon-preview-load',
            ],
          ],
        ];
      }

      $build['grid'][$pack_id]['#slots']['icons'] = $icon_preview;
    }

    ksort($build['grid']);

    // Add the generic mass preview library.
    // Set a specific key to have the list of icons to load for preview.
    $build[] = [
      '#attached' => [
        'library' => ['ui_icons/ui_icons.preview'],
        'drupalSettings' => [
          'ui_icons_preview_data' => [
            'icon_full_ids' => $icon_preview_ids,
            'settings' => ['size' => self::PREVIEW_ICON_SIZE],
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * Sets whether the library show disabled icon pack.
   *
   * @param string $mode
   *   Valid values are 'on' and 'off'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the index page.
   */
  public function modeLibrary(string $mode): RedirectResponse {
    $temp_store = $this->tempStoreFactory->get('icon_library');
    $temp_store->set('disabled', $mode);
    return $this->redirect('ui_icons_library.index');
  }

  /**
   * Gets the title of the icon pack.
   *
   * @param string $pack_id
   *   The ID of the icon pack.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title of the icon pack
   */
  public function getTitle(string $pack_id): TranslatableMarkup {
    $icon_pack = $this->pluginManagerIconPack->getDefinitions();

    if (isset($icon_pack[$pack_id])) {
      return $this->t('Icons @name Pack', ['@name' => $icon_pack[$pack_id]['label'] ?? $pack_id]);
    }

    return $this->t('View Icon Pack');
  }

}
