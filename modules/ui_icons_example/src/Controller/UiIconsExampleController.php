<?php

declare(strict_types=1);

namespace Drupal\ui_icons_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for UI Icons example routes.
 *
 * @codeCoverageIgnore
 */
final class UiIconsExampleController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly UiIconsetManagerInterface $pluginManagerUiIconset,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('plugin.manager.ui_iconset'),
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

    $icons = $this->pluginManagerUiIconset->getIcons();
    $icons = array_splice($icons, 3, 10);

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
      $template = '{{ icon("' . $icon->getIconsetId() . '", "' . $icon->getName() . '", { width: 100, height: 100 }) }}';
      $build['twig'][]['code'] = [
        '#markup' => '<pre><code>' . $template . '</code></pre>',
      ];
      $build['twig'][]['example'] = [
        '#type' => 'inline_template',
        '#template' => $template,
      ];

      $build['element'][] = ['#markup' => '<h3>' . $icon->getName() . ' - ' . $icon->getIconsetLabel() . '</h3>'];
      $base = [
        '#type' => 'ui_icon',
        '#iconset' => $icon->getIconsetId(),
        '#icon' => $icon->getName(),
        '#options' => [
          'width' => 50,
          'height' => 50,
        ],
        '#prefix' => '<div class="form-item">',
        '#suffix' => '</div>&nbsp;&nbsp;',
      ];
      $build['element'][] = $base;
      $base['#options'] = [
        'width' => 100,
        'height' => 100,
      ];
      $build['element'][] = $base;
      $base['#options'] = [
        'width' => 150,
        'height' => 150,
      ];
      $build['element'][] = $base;
      $base['#options'] = [
        'width' => 200,
        'height' => 200,
      ];
      $build['element'][] = $base;
      $build['element'][] = ['#markup' => '<hr>'];
    }

    return $build;
  }

}
