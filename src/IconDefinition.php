<?php

declare(strict_types=1);

namespace Drupal\ui_icons;

use Drupal\ui_icons\Exception\IconDefinitionInvalidDataException;

/**
 * Handle a UI Icon definition.
 */
class IconDefinition implements IconDefinitionInterface {

  /**
   * Constructor for IconDefinition.
   *
   * @param string $icon_id
   *   The id of the icon.
   * @param string $source
   *   The source of the icon.
   * @param array $data
   *   The additional data of the icon.
   * @param string|null $group
   *   The group of the icon (optional).
   */
  private function __construct(
    private string $icon_id,
    private string $source,
    private array $data,
    private ?string $group = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(string $icon_id, string $source, array $data, ?string $group = NULL): self {
    self::validateData($icon_id, $source, $data);
    return new self($icon_id, $source, $data, $group);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return ucfirst(str_replace(['-', '_', '.'], ' ', $this->icon_id));
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return $this->getIconPackId() . ':' . $this->getIconId();
  }

  /**
   * {@inheritdoc}
   */
  public function getIconId(): string {
    return $this->icon_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource(): string {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup(): string {
    return $this->group ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getContent(): ?string {
    if (!isset($this->data['content'])) {
      return NULL;
    }
    if (empty($this->data['content'])) {
      return NULL;
    }
    return $this->data['content'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate(): string {
    return $this->data['template'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary(): ?string {
    return $this->data['library'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIconPackId(): string {
    return $this->data['icon_pack_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getIconPackLabel(): string {
    return $this->data['icon_pack_label'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderable(array $settings = []): array {
    return [
      '#type' => 'ui_icon',
      '#icon_pack' => $this->getIconPackId(),
      '#icon' => $this->getIconId(),
      '#settings' => $settings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPreview(array $settings = []): array {
    $label = sprintf('%s - %s', $this->getLabel(), $this->getIconPackId());

    if ($preview = $this->data['preview'] ?? NULL) {
      return [
        '#type' => 'inline_template',
        '#template' => $preview,
        '#context' => [
          'id' => $this->getIconId(),
          'label' => $label,
          'pack_label' => $this->getIconPackLabel(),
          'source' => $this->getSource(),
          'extractor' => $this->data['extractor'] ?? '',
          'settings' => $settings,
        ],
      ];
    }

    $renderable = [
      '#theme' => 'icon_preview',
      '#id' => $this->getIconId(),
      '#extractor' => $this->data['extractor'] ?? '',
      '#label' => $label,
      '#pack_label' => $this->getIconPackLabel(),
      '#source' => $this->getSource(),
      '#settings' => $settings,
      '#library' => $this->getLibrary(),
    ];

    if ($this->getLibrary()) {
      $renderable['#attached'] = ['library' => [$this->getLibrary()]];
    }

    return $renderable;
  }

  /**
   * Basic validation before creating the icon.
   *
   * @param string $icon_id
   *   The id of the icon.
   * @param string $source
   *   The source of the icon.
   * @param array $data
   *   The additional data of the icon.
   */
  private static function validateData(string $icon_id, string $source, array $data): void {
    $errors = NULL;

    // Empty can have "0" as false positive.
    if ('' === $icon_id) {
      $errors[] = 'Empty icon_id provided';
    }
    // @todo test source is valid? ie path or url?
    if (empty($source)) {
      $errors[] = 'Empty source provided';
    }

    if (!isset($data['icon_pack_id']) || empty($data['icon_pack_id'])) {
      $errors[] = 'Missing Icon Pack Id in data';
    }

    if ($errors) {
      throw new IconDefinitionInvalidDataException(implode('. ', $errors) . '.');
    }
  }

}
