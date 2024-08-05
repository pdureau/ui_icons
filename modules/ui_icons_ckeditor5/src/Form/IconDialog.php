<?php

declare(strict_types=1);

namespace Drupal\ui_icons_ckeditor5\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\filter\FilterFormatInterface;
use Drupal\ui_icons\IconDefinitionInterface;
use Drupal\ui_icons\Plugin\UiIconsetManagerInterface;
use Drupal\ui_icons\Plugin\UiIconsExtractorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a UI Icons Ckeditor5 form.
 */
final class IconDialog extends FormBase {

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
    return 'ui_icons_ckeditor5_icon_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\filter\FilterFormatInterface $filter_format
   *   The text editor format to which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?FilterFormatInterface $filter_format = NULL): array {
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="editor-icon-dialog-form">';
    $form['#suffix'] = '</div>';

    $allowed_iconset = $filter_format->filters('icon_embed')->getConfiguration()['settings']['allowed_iconset'];

    $form['icon'] = [
      '#type' => 'ui_icon_autocomplete',
      '#title' => $this->t('Icon Name'),
      '#size' => 35,
      '#required' => TRUE,
      '#allowed_iconset' => $allowed_iconset,
      '#show_settings' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
      // Prevent this hidden element from being tabbable.
      '#attributes' => [
        'tabindex' => -1,
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#editor-icon-dialog-form', $form));
    }
    else {
      $values = [];
      $value = $form_state->getValue('icon');
      $icon = $value['icon'];
      if ($icon instanceof IconDefinitionInterface) {
        $settings = $value['settings'] ?? [];
        $values = [
          'settings' => [
            'icon' => $icon->getId(),
            'icon_settings' => reset($settings[$icon->getIconsetId()]),
          ],
        ];
      }
      else {
        $values = [
          'settings' => [
            'icon' => NULL,
          ],
        ];
      }
      $response->addCommand(new EditorDialogSave($values));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
