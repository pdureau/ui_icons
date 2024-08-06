<?php

declare(strict_types=1);

namespace Drupal\ui_icons_ckeditor5\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller which renders a preview of the provided icon.
 */
final class IconFilterController implements ContainerInjectionInterface {

  public function __construct(
    protected UiIconsetManagerInterface $pluginManagerUiIconset,
    protected RendererInterface $renderer,
  ) {
    $this->pluginManagerUiIconset = $pluginManagerUiIconset;
    $this->renderer = $renderer;
  }

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
   * Preview an icon.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The icon string rendered.
   */
  public function preview(Request $request): Response {
    $icon_id = (string) $request->query->get('icon_id');
    if ($icon_id == '') {
      throw new NotFoundHttpException();
    }
    $options = [];
    $query_options = (string) $request->query->get('options');
    if ($query_options !== '' && json_validate($query_options)) {
      $options = json_decode($query_options, TRUE);
    }

    $icon = $this->pluginManagerUiIconset->getIcon($icon_id);
    // Use default options if none set.
    if (empty($options)) {
      [$iconset_id] = explode(':', $icon_id);
      $options = $this->pluginManagerUiIconset->getExtractorFormDefaults($iconset_id);
    }

    $build = $icon->getRenderable($options);
    $html = $this->renderer->renderInIsolation($build);

    return (new Response((string) $html, 200))
      // Do not allow any intermediary to cache the response, only the end user.
      ->setPrivate()
      // Allow the end user to cache it for up to 5 minutes.
      ->setMaxAge(300);
  }

}
