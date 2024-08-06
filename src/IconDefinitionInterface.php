<?php

declare(strict_types=1);

namespace Drupal\ui_icons;

/**
 * Interface for UI Icons icon definition.
 */
interface IconDefinitionInterface {

  /**
   * Create an icon definition.
   *
   * @param string $name
   *   The name of the icon.
   * @param string $path
   *   The path of the icon.
   * @param array $data
   *   The additional data of the icon.
   * @param string|null $group
   *   The group of the icon (optional).
   *
   * @return self
   *   The icon definition.
   */
  public static function create(string $name, string $path, array $data, ?string $group = NULL): self;

  /**
   * Get the Icon id.
   *
   * @return string
   *   The icon id as iconset_id:icon_id.
   */
  public function getId(): string;

  /**
   * Get the Icon name.
   *
   * @return string
   *   The icon name.
   */
  public function getName(): string;

  /**
   * Get the Icon source.
   *
   * @return string
   *   The icon source.
   */
  public function getSource(): string;

  /**
   * Get the Icon group.
   *
   * @return string
   *   The icon group.
   */
  public function getGroup(): string;

  /**
   * Get the Icon content.
   *
   * @return string
   *   The icon content.
   */
  public function getContent(): string;

  /**
   * Get the Iconset id.
   *
   * @return string
   *   The Iconset id.
   */
  public function getIconsetId(): string;

  /**
   * Get the Iconset label.
   *
   * @return string
   *   The Iconset label.
   */
  public function getIconsetLabel(): string;

  /**
   * Get the Icon renderable array.
   *
   * @param array $options
   *   Options to pass to the renderable for context.
   *
   * @return array
   *   The Icon renderable.
   */
  public function getRenderable(array $options = []): array;

}