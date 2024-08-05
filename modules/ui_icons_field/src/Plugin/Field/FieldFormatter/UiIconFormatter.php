<?php

declare(strict_types=1);

namespace Drupal\ui_icons_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;
use Drupal\ui_icons\IconDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ui_icon_formatter' formatter.
 */
#[FieldFormatter(
  id: 'ui_icon_formatter',
  label: new TranslatableMarkup('UI Icon'),
  field_types: [
    'ui_icon',
  ],
)]
class UiIconFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs an UiIconFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The Plugin settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
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
    string $label,
    string $view_mode,
    array $third_party_settings,
    protected UiIconsetManagerInterface $pluginManagerUiIconset,
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );
    $this->pluginManagerUiIconset = $pluginManagerUiIconset;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.ui_iconset')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'icon_settings' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    if ($this->getSetting('icon_settings')) {
      $summary[] = $this->t('Specific icon settings saved');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);

    // @todo get widget settings to know if field has extractor settings enabled.
    $elements['iconset_notice'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('If the form display allow user to set his own settings, these values will be ignored.'),
      '#attributes' => ['class' => ['description']],
    ];

    $icon_settings = $this->getSetting('icon_settings') ?? [];

    $this->pluginManagerUiIconset->getExtractorPluginForms($elements, $form_state, $icon_settings, [], TRUE);

    // Placeholder to get all settings serialized as the form keys are dynamic
    // and based on iconset definition options.
    // @todo probably change to #element_submit when available in
    // https://drupal.org/i/2820359.
    $elements['icon_settings'] = [
      '#type' => 'hidden',
      '#element_validate' => [
        [$this, 'validateSettings'],
      ],
    ];

    return $elements;
  }

  /**
   * Validation callback for extractor settings element.
   *
   * All extractor settings form values are serialized in a single declared
   * icon_settings form key.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validateSettings(array $element, FormStateInterface $form_state, &$complete_form): void {
    $values = $form_state->getValues();
    $name = $this->fieldDefinition->getName();

    $settings = $values['fields'][$name]['settings_edit_form']['settings'] ?? [];
    unset($settings['icon_settings']);

    // @todo do we need configuration validation of plugin form?
    $form_state->setValueForElement($element, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    foreach ($items as $delta => $item) {
      if ($item->isEmpty()) {
        continue;
      }

      $icon_id = $item->get('target_id')->getValue();
      if ($icon_id === NULL) {
        continue;
      }

      $icon = $this->pluginManagerUiIconset->getIcon($icon_id);
      if ($icon === NULL || !$icon instanceof IconDefinitionInterface) {
        continue;
      }

      $iconset_id = $icon->getIconsetId();

      // Priority is to look for widget settings, then formatter, then defaults
      // from definition.
      $settings = $item->get('settings')->getValue();
      if (!empty($settings)) {
        $settings = reset($settings[$iconset_id]);
      }
      else {
        $formatter_settings = $this->getSetting('icon_settings') ?? [];
        if (isset($formatter_settings[$iconset_id])) {
          $settings = reset($formatter_settings[$iconset_id]);
        }
        else {
          // If the settings form has never been saved, we need to get extractor
          // default values if set.
          // @todo move to getRenderable()?
          $settings = $this->pluginManagerUiIconset->getExtractorFormDefaults($iconset_id);
        }
      }

      $elements[$delta] = $icon->getRenderable($settings);
    }

    return $elements;
  }

}
