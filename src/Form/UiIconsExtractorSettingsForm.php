<?php

declare(strict_types=1);

namespace Drupal\ui_icons\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Handle UI Icons extractor settings form.
 */
class UiIconsExtractorSettingsForm {

  /**
   * Create the form API element from extractor options.
   *
   * @param array $options
   *   The options from extractor definition.
   * @param \Drupal\Core\Form\FormStateInterface|null $form_state
   *   The from state used to get values if n form context.
   *
   * @return array
   *   The form API generated.
   *
   * @todo split for less complexity.
   */
  public static function generateSettingsForm(array $options, ?FormStateInterface $form_state = NULL): array {
    $saved_values = [];
    if ($form_state) {
      /** @var \Drupal\Core\Form\SubformStateInterface $form_state */
      $saved_values = $form_state->getCompleteFormState()->getValue('saved_values') ?? [];
    }

    $form = [];
    foreach ($options as $key => $definition) {
      $type = $definition['type'] ?? NULL;
      $extra = [];
      switch ($type) {
        case 'string':
          $type = 'textfield';
          $extra = [
            '#size' => $definition['size'] ?? 60,
          ];
          break;

        case 'number':
        case 'integer':
          $type = 'number';
          $extra = [
            '#min' => $definition['min'] ?? 1,
            '#max' => $definition['max'] ?? 9999,
            '#step' => $definition['step'] ?? 1,
          ];
          break;

        case 'float':
        case 'decimal':
          $type = 'number';
          $extra = [
            '#min' => $definition['min'] ?? 1,
            '#max' => $definition['max'] ?? 9999,
            '#step' => $definition['step'] ?? 0.01,
          ];
          break;

        case 'boolean':
          $type = 'checkbox';
          break;

        case 'color':
          $type = 'color';
          break;

        case 'range':
          $type = 'range';
          $extra = [
            '#min' => $definition['min'] ?? 1,
            '#max' => $definition['max'] ?? 100,
            '#step' => $definition['step'] ?? 1,
          ];
          break;

        default:
          $type = 'textfield';
          break;
      }

      $form[$key] = [
        '#type' => isset($definition['enum']) ? 'select' : $type,
        '#title' => $definition['title'] ?? $key,
        '#description' => $definition['description'] ?? '',
      ] + $extra;

      // Specific properties that should not be set empty.
      if (isset($definition['enum'])) {
        $form[$key]['#options'] = array_combine($definition['enum'], $definition['enum']);
        unset($form[$key]['#size']);
      }
      if (isset($definition['size'])) {
        $form[$key]['#size'] = $definition['size'];
      }
      if (isset($definition['maxlength'])) {
        $form[$key]['#maxlength'] = $definition['maxlength'];
      }
      if (isset($definition['pattern'])) {
        $form[$key]['#pattern'] = $definition['pattern'];
      }
      if (isset($definition['required'])) {
        $form[$key]['#required'] = $definition['required'];
      }
      if (isset($definition['prefix'])) {
        $form[$key]['#field_prefix'] = $definition['prefix'];
      }
      if (isset($definition['suffix'])) {
        $form[$key]['#field_suffix'] = $definition['suffix'];
      }
      if (isset($definition['placeholder'])) {
        $form[$key]['#placeholder'] = $definition['placeholder'];
      }

      if (isset($saved_values[$key])) {
        $form[$key]['#default_value'] = $saved_values[$key];
      }
      elseif (isset($definition['default'])) {
        $form[$key]['#default_value'] = $definition['default'];
      }
    }

    return $form;
  }

}
