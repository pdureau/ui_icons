<?php

declare(strict_types=1);

namespace Drupal\ui_icons\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ui_icons\Form\IconExtractorSettingsForm;
use Drupal\ui_icons\IconDefinition;

/**
 * Base class for ui_icons_extractor plugins.
 */
abstract class IconExtractorBase extends PluginBase implements IconExtractorInterface, PluginWithFormsInterface {

  use StringTranslationTrait;
  use PluginWithFormsTrait;

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function description(): string {
    return (string) $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    if (!isset($this->configuration['settings'])) {
      return $form;
    }

    return IconExtractorSettingsForm::generateSettingsForm($this->configuration['settings'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public static function createIcon(string $icon_id, string $path, array $data, ?string $group = NULL): IconDefinition {
    $data = self::filterDataFromDefinition($data);
    return IconDefinition::create($icon_id, $path, $data, $group);
  }

  /**
   * Clean the icon data object values.
   *
   * @param array $definition
   *   The definition used as data.
   *
   * @return array
   *   The clean data definition.
   */
  private static function filterDataFromDefinition(array $definition): array {
    return [
      'icon_pack_id' => $definition['icon_pack_id'],
      'icon_pack_label' => $definition['icon_pack_label'] ?? '',
      'template' => $definition['template'] ?? NULL,
      'library' => $definition['library'] ?? NULL,
      'content' => $definition['content'] ?? '',
      'extractor' => $definition['extractor'] ?? '',
      'preview' => $definition['preview'] ?? '',
    ];
  }

}
