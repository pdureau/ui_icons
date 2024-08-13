<?php

declare(strict_types=1);

namespace Drupal\ui_icons_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Element;
use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;
use Drupal\ui_icons\Plugin\UiIconsExtractorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a UI Icons example form.
 *
 * phpcs:disable
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 *
 * @codeCoverageIgnore
 */
final class UiIconsExampleForm extends FormBase {

  public function __construct(
    protected UiIconsetManagerInterface $pluginManagerUiIconset,
    protected UiIconsExtractorPluginManager $iconsetExtractorManager,
  ) {
    $this->pluginManagerUiIconset = $pluginManagerUiIconset;
    $this->iconsetExtractorManager = $iconsetExtractorManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.ui_iconset'),
      $container->get('plugin.manager.ui_icons_extractor'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ui_icons_example_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $iconset = $this->pluginManagerUiIconset->getDefinitions();

    $form = [];
    $form['#tree'] = TRUE;

    $form['iconset'] = [
      '#type' => 'details',
      '#title' => $this->t('Iconset field examples'),
      '#open' => TRUE,
      '#weight' => 2,
    ];

    $form['iconset']['iconset_checkboxes'] = [
      '#type' => 'details',
      '#title' => $this->t('Iconset multiple checkbox with description and link'),
      '#open' => TRUE,
    ];

    $options = $this->pluginManagerUiIconset->listIconsetWithDescriptionOptions();
    $options_title = [];
    foreach ($options as $key => $title) {
      $part = explode(' - ', $title);
      $options_title[$key] = $part[0] ?? 'n/a';
      $form['iconset']['iconset_checkboxes'][$key] = [
        '#type' => 'checkbox',
        '#title' => $part[0] ?? 'n/a',
        '#description' => $part[1] ?? '',
      ];
    }

    $form['iconset']['iconset_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Iconset selector with description'),
      '#options' => $options,
    ];

    $form['iconset']['iconset_select_title'] = [
      '#type' => 'select',
      '#title' => $this->t('Iconset selector title only'),
      '#options' => $options_title,
    ];

    $form['iconset']['iconset_select_multiple'] = [
      '#type' => 'select',
      '#title' => $this->t('Iconset selector multiple'),
      '#options' => $options_title,
      '#multiple' => TRUE,
    ];

    $form['icons'] = [
      '#type' => 'details',
      '#title' => $this->t('Icons selector examples'),
      '#open' => TRUE,
      '#weight' => 1,
    ];

    $form['icons']['icons_autocomplete'] = [
      '#type' => 'ui_icon_autocomplete',
      '#title' => $this->t('Icon selector'),
      '#placeholder' => $this->t('Start typing icon name'),
    ];

    $form['icons']['icons_autocomplete_settings'] = [
      '#type' => 'ui_icon_autocomplete',
      '#title' => $this->t('Icon selector with settings'),
      '#placeholder' => $this->t('Start typing icon name'),
      '#show_settings' => TRUE,
    ];

    $allowed = array_slice(array_keys($iconset), 0, 1);
    $names = $this->pluginManagerUiIconset->listIconsetOptions();
    $form['icons']['icons_autocomplete_limit'] = [
      '#type' => 'ui_icon_autocomplete',
      '#title' => $this->t('Icon selector limited'),
      '#description' => $this->t('Limited to: @name.', ['@name' => $names[$allowed[0]]]),
      '#placeholder' => $this->t('Start typing icon name'),
      '#allowed_iconset' => $allowed,
    ];

    // No full select as we could have thousands of icons.
    $options = ['' => $this->t('- Select -')];
    $options += $this->pluginManagerUiIconset->listOptions($allowed);
    $form['icons']['icons_select_limited'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon select limited'),
      '#description' => $this->t('Limited to 20 first: @name.', ['@name' => $names[$allowed[0]]]),
      '#options' => array_slice($options, 0, 20),
      '#sort_options' => TRUE,
    ];

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Extractor settings'),
      '#open' => TRUE,
      '#weight' => 1,
    ];

    // We store the iconset definition to have access in validate and submit
    // and avoid reloading it.
    $form['iconset_definition'] = [
      '#type' => 'hidden',
      '#value' => serialize($iconset),
    ];

    // Add our extractor forms.
    $this->pluginManagerUiIconset->getExtractorPluginForms($form['settings'], $form_state);
    foreach (Element::children($form['settings']) as $iconset_id) {
      $form['settings'][$iconset_id]['#type'] = 'details';
      $form['settings'][$iconset_id]['#title'] = $iconset_id;
    }

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Example to run all form extractor plugin validate methods.
    $iconset = $this->pluginManagerUiIconset->getDefinitions();
    unset($iconset['_icons_loaded']);
    $extractor_forms = $this->iconsetExtractorManager->getExtractorForms($iconset);

    $message = [];

    foreach ($iconset as $iconset_id => $plugin) {
      if (!isset($plugin['label']) || !isset($plugin['extractor'])) {
        continue;
      }

      $extractor_id = $plugin['extractor'];

      $params = ['@iconset_id' => $iconset_id, '@extractor_id' => $extractor_id];
      $message[] = $this->t('Run validate extractor for: @iconset_id:@extractor_id', $params);

      // Isolate the form part of the extractor to validate.
      $subform = $form['settings'][$iconset_id][$extractor_id];
      $extractor_forms[$extractor_id]->validateConfigurationForm($subform, SubformState::createForSubform($subform, $form, $form_state));
    }
    
    $this->messenger()->addStatus(implode("<br>", $message));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Example to run all form extractor plugin submit methods.
    $iconset = $this->pluginManagerUiIconset->getDefinitions();
    unset($iconset['_icons_loaded']);
    $extractor_forms = $this->iconsetExtractorManager->getExtractorForms($iconset);

    $message = [];

    foreach ($iconset as $iconset_id => $plugin) {
      if (!isset($plugin['label']) || !isset($plugin['extractor'])) {
        continue;
      }

      $extractor_id = $plugin['extractor'];

      $params = ['@iconset_id' => $iconset_id, '@extractor_id' => $extractor_id];
      $message[] = $this->t('Run submit extractor for: @iconset_id:@extractor_id', $params);

      // Isolate the form part of the extractor to validate.
      $subform = $form['settings'][$iconset_id][$extractor_id];
      $extractor_forms[$extractor_id]->submitConfigurationForm($subform, SubformState::createForSubform($subform, $form, $form_state));
    }
    
    $this->messenger()->addStatus(implode("<br>", $message));

    $message = [];
    // Process the form, only display values for example.
    $values = $form_state->getValues();

    foreach ($values['icons'] as $key_form => $icons) {
      $message[] = $key_form . ': ' . (is_array($icons) ? implode(', ', $icons) : $icons);
    }
    foreach ($values['iconset'] as $key_form => $iconset) {
      $message[] = $key_form . ': ' . (is_array($iconset) ? implode(', ', $iconset) : $iconset);
    }

    foreach ($values['settings'] as $settings) {
      if (!is_array($settings)) {
        continue;
      }
      foreach ($settings as $plugin_id => $plugin_values) {
        if (!is_array($plugin_values)) {
          continue;
        }
        $message[] = $this->t('Plugin @name submitted:', ['@name' => $plugin_id]);
        foreach ($plugin_values as $key => $value) {
          if (FALSE !== strpos($key, 'form_') || 'op' === $key || 'submit' === $key) {
            continue;
          }
          $message[] = $key . ': ' . $value;
        }
      }
    }

    $this->messenger()->addStatus(implode("<br>", $message));
  }

}
