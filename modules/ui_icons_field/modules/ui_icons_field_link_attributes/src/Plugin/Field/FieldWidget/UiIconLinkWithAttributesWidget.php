<?php

namespace Drupal\ui_icons_field_link_attributes\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\link_attributes\LinkAttributesManager;
use Drupal\link_attributes\LinkWithAttributesWidgetTrait;
use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;
use Drupal\ui_icons_field_link\Plugin\Field\FieldWidget\UiIconLinkWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'link field with attributes' widget.
 */
#[FieldWidget(
  id: 'ui_icon_link_attributes_widget',
  label: new TranslatableMarkup('Link UI icon (with attributes)'),
  field_types: ['link'],
)]
class UiIconLinkWithAttributesWidget extends UiIconLinkWidget implements ContainerFactoryPluginInterface {

  use LinkWithAttributesWidgetTrait {
    defaultSettings as protected traitDefaultSettings;
    formElement as protected traitFormElement;
    settingsForm as protected traitSettingsForm;
    settingsSummary as protected traitSettingsSummary;
  }

  /**
   * Constructs an UiIconLinkWithAttributesWidget instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The Plugin settings.
   * @param array $third_party_settings
   *   The Plugin third party settings.
   * @param \Drupal\ui_icons\Plugin\UiIconsetManagerInterface $pluginManagerUiIconset
   *   The ui icons manager.
   * @param \Drupal\link_attributes\LinkAttributesManager $linkAttributesManager
   *   The link attributes manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected UiIconsetManagerInterface $pluginManagerUiIconset,
    protected LinkAttributesManager $linkAttributesManager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $pluginManagerUiIconset);
    $this->linkAttributesManager = $linkAttributesManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.ui_iconset'),
      $container->get('plugin.manager.link_attributes'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return self::traitDefaultSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = $this->traitSettingsForm($form, $form_state);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = $this->traitSettingsSummary();
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = $this->traitFormElement($items, $delta, $element, $form, $form_state);
    return $element;
  }

}
