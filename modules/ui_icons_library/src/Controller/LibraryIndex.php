<?php

declare(strict_types=1);

namespace Drupal\ui_icons_library\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface;
use Drupal\Core\Url;
use Drupal\ui_icons\IconPreview;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for UI Icons routes.
 */
class LibraryIndex extends ControllerBase {

  private const PREVIEW_ICON_NUM = 30;
  private const PREVIEW_ICON_SIZE = 40;

  public function __construct(
    private readonly IconPackManagerInterface $pluginManagerIconPack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.icon_pack'),
    );
  }

  /**
   * Index of Pack list.
   *
   * @return array<string, mixed>
   *   Render array of packs.
   */
  public function index(): array {
    $build = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => ['class' => ['description']],
      '#value' => $this->t('List of Icon packs available.'),
    ];

    $build['grid'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['card_grid']],
    ];

    $icon_pack = $this->pluginManagerIconPack->getDefinitions();
    foreach ($icon_pack as $pack_id => $pack_definition) {

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

      if (count($icons) > self::PREVIEW_ICON_NUM) {
        $rand_keys = array_rand($icons, self::PREVIEW_ICON_NUM);
        $icons = array_intersect_key($icons, array_flip($rand_keys));
      }

      $icon_preview = [];
      foreach (array_keys($icons) as $icon_id) {
        // @todo avoid load icon and pass template for preview.
        $icon = $this->pluginManagerIconPack->getIcon($icon_id);
        $icon_preview[] = IconPreview::getPreview($icon, ['size' => self::PREVIEW_ICON_SIZE]);
      }

      $build['grid'][$pack_id]['#slots']['icons'] = $icon_preview;
    }

    ksort($build['grid']);

    return $build;
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
