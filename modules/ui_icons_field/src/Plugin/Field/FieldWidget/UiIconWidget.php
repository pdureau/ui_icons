<?php

declare(strict_types=1);

namespace Drupal\ui_icons_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_icons\IconDefinitionInterface;
use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ui_icon_widget' widget.
 */
#[FieldWidget(
  id: 'ui_icon_widget',
  label: new TranslatableMarkup('UI Icon'),
  field_types: ['ui_icon'],
)]
class UiIconWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a UiIconWidget object.
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
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected UiIconsetManagerInterface $pluginManagerUiIconset,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->pluginManagerUiIconset = $pluginManagerUiIconset;
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'allowed_iconset' => [],
      'show_settings' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);

    $elements['allowed_iconset'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Icon set selection'),
      '#description' => $this->t('Select iconset to make available. Default to all if none selected.'),
      '#options' => $this->pluginManagerUiIconset->listIconsetWithDescriptionOptions(),
      '#default_value' => $this->getSetting('allowed_iconset'),
      '#multiple' => TRUE,
    ];

    $elements['show_settings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow icon settings form'),
      '#description' => $this->t('If selected, icon settings will be made available for all enabled Icon set above. Note that display setting on these field will be ignored.'),
      '#default_value' => $this->getSetting('show_settings'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    $settings = $this->getSettings();

    $allowed_iconset = array_filter($settings['allowed_iconset']);

    if (!empty($allowed_iconset)) {
      $labels = $this->pluginManagerUiIconset->listIconsetOptions();
      $list = array_intersect_key($labels, $allowed_iconset);
      $summary[] = $this->t('With Icon set: @set', ['@set' => implode(', ', $list)]);
    }
    else {
      $summary[] = $this->t('All icon sets available for selection');
    }

    if (!empty($settings['show_settings'])) {
      $summary[] = $this->t('Icon settings enabled');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $settings = $this->getSettings();

    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    $item = $items[$delta];

    $element['value'] = [
      '#type' => 'ui_icon_autocomplete',
      '#default_value' => NULL,
      '#title' => $cardinality === 1 ? $this->fieldDefinition->getLabel() : $this->t('Icon'),
      '#allowed_iconset' => array_filter($settings['allowed_iconset'] ?? []),
      '#show_settings' => $settings['show_settings'],
      '#required' => $element['#required'] ?? FALSE,
    ];

    if ($item->target_id) {
      $element['value']['#default_value'] = $item->target_id;
      if (TRUE == $settings['show_settings'] && $item->settings) {
        // @todo default from formatter before definition?
        $element['value']['#default_settings'] = $item->settings;
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    foreach ($values as &$item) {
      if (!empty($item['value']['icon']) && $item['value']['icon'] instanceof IconDefinitionInterface) {
        $icon = $item['value']['icon'];
        $item['target_id'] = $icon->getId();
        $settings = [];
        if (isset($item['value']['settings'])) {
          $settings = $item['value']['settings'];
        }
        $item['settings'] = $settings;
      }
    }

    return $values;
  }

}
