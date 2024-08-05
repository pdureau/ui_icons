<?php

declare(strict_types=1);

namespace Drupal\ui_icons\PluginForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormBase;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * The ui iconset extractor form plugin.
 *
 * @internal
 */
class UiIconsetExtractorForm extends PluginFormBase implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['ui-icons-settings'] = [
      '#type' => 'container',
      '#prefix' => '<div id="ui-icons-settings-wrapper">',
      '#suffix' => '</div>',
    ];

    // @phpstan-ignore-next-line
    $form = $this->plugin->buildConfigurationForm($form['ui-icons-settings'], $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    // @phpstan-ignore-next-line
    $this->plugin->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    // @phpstan-ignore-next-line
    $this->plugin->submitConfigurationForm($form, $form_state);
  }

}
