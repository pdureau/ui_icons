<?php

declare(strict_types=1);

namespace Drupal\ui_icons_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ui_icons\Plugin\IconPackManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for UI Icons example routes.
 *
 * @codeCoverageIgnore
 */
final class IconExampleController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly IconPackManagerInterface $pluginManagerIconPack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('plugin.manager.ui_icons_pack'),
    );
  }

  /**
   * Builds the response.
   *
   * @return array
   *   The build render.
   */
  public function __invoke(): array {
    $build = [];

    $all_icons = $this->pluginManagerIconPack->getIcons();
    $rand_keys = array_rand($all_icons, 10);
    $icons = array_intersect_key($all_icons, array_flip($rand_keys));

    $build['twig'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Twig function example'),
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    $build['element'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Element example `ui_icon`'),
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    foreach ($icons as $icon) {
      $width = rand(20, 140);
      $template = '{{ icon("' . $icon->getIconPackId() . '", "' . $icon->getIconId() . '", { width: ' . $width . ', height: ' . $width . '}) }}';
      $build['twig'][]['code'] = [
        '#markup' => '<pre><code>' . $template . '</code></pre>',
      ];
      $build['twig'][]['example'] = [
        '#type' => 'inline_template',
        '#template' => $template,
      ];

      $build['element'][] = ['#markup' => '<h3>' . $icon->getLabel() . ' - ' . $icon->getIconPackLabel() . '</h3>'];
      $base = [
        '#type' => 'ui_icon',
        '#icon_pack' => $icon->getIconPackId(),
        '#icon' => $icon->getIconId(),
        '#settings' => [
          'width' => 24,
          'height' => 24,
        ],
        '#prefix' => '<div class="form-item">',
        '#suffix' => '</div>&nbsp;&nbsp;',
      ];
      $build['element'][] = $base;
      $base['#settings'] = [
        'width' => 48,
        'height' => 48,
      ];
      $build['element'][] = $base;
      $base['#settings'] = [
        'width' => 96,
        'height' => 96,
      ];
      $build['element'][] = $base;
      $base['#settings'] = [
        'width' => 128,
        'height' => 128,
      ];
      $build['element'][] = $base;
      $base['#settings'] = [
        'width' => 64,
        'height' => 64,
      ];
      $build['element'][] = $base;
      $build['element'][] = ['#markup' => '<hr>'];
    }

    return $build;
  }

}
