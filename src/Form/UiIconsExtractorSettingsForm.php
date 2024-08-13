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
      switch ($type) {
        case 'string':
          $type = 'textfield';
          break;

        case 'number':
        case 'integer':
          $type = 'number';
          break;

        case 'float':
        case 'decimal':
          $type = 'number';
          break;

        case 'boolean':
          $type = 'checkbox';
          break;

        case 'color':
          $type = 'color';
          break;

        // @todo create datalist to have ticker
        // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/range#adding_tick_marks
        case 'range':
          $type = 'range';
          break;

        default:
          $type = 'textfield';
          break;
      }

      $form[$key] = [
        '#type' => isset($definition['enum']) ? 'select' : $type,
        '#title' => $definition['title'] ?? $key,
      ];

      // Specific properties that should not be set empty.
      if (isset($definition['description'])) {
        $form[$key]['#description'] = $definition['description'];
      }
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

      if ('number' === $type || 'range' === $type) {
        if (isset($definition['min'])) {
          $form[$key]['#min'] = $definition['min'];
        }
        if (isset($definition['max'])) {
          $form[$key]['#max'] = $definition['max'];
        }

        if (isset($definition['step'])) {
          $form[$key]['#step'] = $definition['step'];
        }
        // Default step will be set to 1, check for float number.
        elseif (isset($definition['default']) && 'double' === gettype($definition['default'])) {
          $form[$key]['#step'] = 0.1;
        }
      }
    }

    return $form;
  }

}
