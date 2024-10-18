<?php

declare(strict_types=1);

namespace Drupal\ui_icons_library\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Core\Theme\Icon\IconDefinitionInterface;
use Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function Symfony\Component\String\u;

/**
 * Provides a UI Icons form.
 *
 * @codeCoverageIgnore
 */
final class LibrarySearchForm extends FormBase {

  private const NUM_PER_PAGE = 100;
  private const ICON_DEFAULT_SIZE = 64;

  public function __construct(
    private readonly IconPackManagerInterface $pluginManagerIconPack,
    private readonly PagerManagerInterface $pagerManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.icon_pack'),
      $container->get('pager.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ui_icons_library_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $pack_id = ''): array {
    $session = $this->getRequest()->getSession();
    $values = $session->get('ui_icons_library_search');

    // Build default settings and try to override size for display.
    $default = [
      $pack_id => array_merge(
        $this->pluginManagerIconPack->getExtractorFormDefaults($pack_id),
        [
          'size' => self::ICON_DEFAULT_SIZE,
          'width' => self::ICON_DEFAULT_SIZE,
          'height' => self::ICON_DEFAULT_SIZE,
        ],
      ),
    ];

    $settings = $values[$pack_id]['settings'] ?? $default;
    $search = $values[$pack_id]['search'] ?? '';
    $group = $values[$pack_id]['group'] ?? '';

    $form['#theme'] = 'form_icon_pack';
    $form['pack_id'] = [
      '#type' => 'hidden',
      '#value' => $pack_id,
    ];

    $form['search'] = [
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $search,
      '#title' => $this->t('Keywords'),
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Keywords'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => ['class' => ['button--primary']],
    ];

    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#name' => 'reset',
      '#value' => $this->t('Reset'),
    ];

    $form['actions']['#weight'] = -9;

    $icons_list = $this->pluginManagerIconPack->getIcons([$pack_id]);

    $group_options = [];
    foreach ($icons_list as $icon) {
      $group_id = $icon['group'] ?? NULL;
      if (empty($group_id)) {
        continue;
      }
      $group_name = u($group_id)->snake()->replace('_', ' ')->title(allWords: TRUE);
      $group_options[$group_id] = $group_name;
    }

    if (!empty($group_options)) {
      ksort($group_options);
      $form['group'] = [
        '#type' => 'select',
        '#title_display' => 'invisible',
        '#title' => $this->t('Group'),
        '#default_value' => $group,
        '#options' => ['' => $this->t('- Select group -')] + $group_options,
      ];
    }

    $icons = $this->filterIcons($icons_list, $search, $pack_id, $group, $settings);

    $total = count($icons);
    $pager = $this->pagerManager->createPager($total, self::NUM_PER_PAGE);
    $page = $pager->getCurrentPage();
    $offset = self::NUM_PER_PAGE * $page;

    $icons = array_slice($icons, $offset, self::NUM_PER_PAGE);

    $form['list'] = [
      '#theme' => 'ui_icons_library',
      '#search' => $search,
      '#icons' => $icons,
      '#total' => $total,
      '#available' => count($icons_list),
    ];

    $form['settings'] = ['#tree' => TRUE];
    $this->pluginManagerIconPack->getExtractorPluginForms(
      $form['settings'],
      $form_state,
      $settings,
      [$pack_id => $pack_id],
    );

    $form['pager_top'] = [
      '#type' => 'pager',
    ];

    $form['pager'] = [
      '#type' => 'pager',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $session = $this->getRequest()->getSession();

    $trigger = $form_state->getTriggeringElement();
    if (isset($trigger['#name']) && 'reset' === $trigger['#name']) {
      $session->remove('ui_icons_library_search');
      return;
    }
    $values = $form_state->getValues();
    $pack_id = $values['pack_id'];
    foreach (array_keys($values) as $key) {
      if (FALSE !== strpos($key, 'form_') || 'op' === $key || 'submit' === $key || 'pack_id' === $key) {
        unset($values[$key]);
      }
    }

    $session->set('ui_icons_library_search', [$pack_id => $values]);
  }

  /**
   * Filter icons based on criteria.
   *
   * @param array $icons_list
   *   The list of icons to filter.
   * @param string $search
   *   The search string.
   * @param string $pack_id
   *   The icon set to filter by.
   * @param string $group
   *   The group to filter by.
   * @param array $settings
   *   Settings of the icon.
   *
   * @return array
   *   The filtered list of icons.
   */
  private function filterIcons(array $icons_list, string $search, string $pack_id, string $group, array $settings = []): array {
    $search = mb_strtolower($search);
    $icons = [];
    foreach ($icons_list as $icon_full_id => $icon) {
      $icon_data = explode(IconDefinition::ICON_SEPARATOR, $icon_full_id);
      if (!isset($icon_data[0]) || !isset($icon_data[1])) {
        continue;
      }
      [$icon_pack_id, $icon_id] = $icon_data;

      if (!empty($pack_id) && $pack_id !== $icon_pack_id) {
        continue;
      }
      if (!empty($group) && $group !== $icon['group']) {
        continue;
      }
      if (!empty($search) && !str_contains($icon_id, $search)) {
        continue;
      }
      // Load only icon if needed for performance.
      $icon = $this->pluginManagerIconPack->getIcon($icon_full_id);
      if (!$icon instanceof IconDefinitionInterface) {
        continue;
      }
      $icons[] = $icon->getRenderable($settings[$pack_id]);
    }

    return $icons;
  }

}
