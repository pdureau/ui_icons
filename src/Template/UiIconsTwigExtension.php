<?php

declare(strict_types=1);

namespace Drupal\ui_icons\Template;

use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for UI Icons.
 */
class UiIconsTwigExtension extends AbstractExtension {

  /**
   * Constructs the extension object.
   */
  public function __construct(
    private readonly UiIconsetManagerInterface $pluginManagerUiIconset,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('icon', [$this, 'getIconRenderable']),
    ];
  }

  /**
   * Get an icon renderable.
   *
   * @param string $iconset_id
   *   The icon set ID.
   * @param string $icon_id
   *   The icon ID.
   * @param array $options
   *   An array of options for the icon.
   *
   * @return array
   *   The icon renderable.
   */
  public function getIconRenderable(string $iconset_id, string $icon_id, array $options = []): array {
    $icon = $this->pluginManagerUiIconset->getIcon($iconset_id . ':' . $icon_id);
    if (!$icon) {
      return [];
    }

    return $icon->getRenderable($options);
  }

}
