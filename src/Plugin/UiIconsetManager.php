<?php

declare(strict_types=1);

namespace Drupal\ui_icons\Plugin;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\ui_icons\Exception\IconsetConfigErrorException;
use Drupal\ui_icons\IconDefinitionInterface;

/**
 * Defines a Ui Iconset plugin manager to deal with icons.
 *
 * Extension can define iconset in an EXTENSION_NAME.ui_icons.yml file
 * contained in the extension's base directory. Each iconset has the
 * following structure:
 * @code
 *   MACHINE_NAME:
 *     label: STRING
 *     description: STRING
 *     enabled: BOOL
 *     extractor: MACHINE_NAME
 *     config:
 *       sources: ARRAY
 *       icons: ARRAY
 *     options:
 *       FORM_KEY: OBJECT
 *       ...
 *     template: STRING
 *     library: STRING
 * @endcode
 * For example:
 * @code
 * my_iconset:
 *   label: "My icons"
 *   description: "My UI Icons set to use everywhere."
 *   extractor: svg
 *   config:
 *     sources:
 *       - icons/local_svg/{icon_id}.svg
 *       - icons/local_svg_group/{group}/{icon_id}.svg
 *   options:
 *     width: {
 *       title: 'Please set width'
 *       type: "integer"
 *     }
 *     height: {
 *       title: 'Please set height'
 *       type: "integer"
 *     }
 *   template: '<img src={{ source }} title="{{ title }}" width="{{ width }}" height="{{ height }}"/>'
 *   library: "my_theme/my_lib"
 * @endcode
 *
 * @see plugin_api
 */
class UiIconsetManager extends DefaultPluginManager implements UiIconsetManagerInterface {

  /**
   * Constructs the UiIconsetPluginManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   * @param \Drupal\ui_icons\Plugin\UiIconsExtractorPluginManager $iconsetExtractorManager
   *   The ui_icons plugin extractor service.
   * @param string $appRoot
   *   The application root.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    protected ThemeHandlerInterface $themeHandler,
    CacheBackendInterface $cacheBackend,
    protected UiIconsExtractorPluginManager $iconsetExtractorManager,
    protected string $appRoot,
  ) {
    $this->moduleHandler = $module_handler;
    $this->factory = new ContainerFactory($this);
    $this->alterInfo('ui_icons');
    $this->setCacheBackend($cacheBackend, 'ui_iconset', ['ui_iconset_plugin']);
  }

  /**
   * {@inheritdoc}
   */
  public function getIcons(): array {
    $definitions = $this->getDefinitions();

    // Keep loaded list in the definition to have cache.
    if (isset($definitions['_icons_loaded'])) {
      return $definitions['_icons_loaded'];
    }

    $icons = [];
    foreach ($definitions as $definition) {
      /** @var \Drupal\ui_icons\Plugin\UiIconsExtractorPluginInterface $extractor */
      $extractor = $this->iconsetExtractorManager->createInstance($definition['extractor'], $definition);
      $icons += $extractor->getIcons();
    }

    $definitions['_icons_loaded'] = $icons;
    $this->setCachedDefinitions($definitions);

    return $icons;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(string $icon_id): ?IconDefinitionInterface {
    $icons = $this->getIcons();
    if (isset($icons[$icon_id])) {
      return $icons[$icon_id];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function listIconsetOptions(): array {
    $iconset = $this->getCleanDefinitions();

    $options = [];
    foreach ($iconset as $key => $set) {
      $options[$key] = $set['label'];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function listIconsetWithDescriptionOptions(): array {
    $iconset = $this->getCleanDefinitions();

    $options = [];
    foreach ($iconset as $key => $set) {
      $options[$key] = $set['label'];
      if (isset($set['description'])) {
        $options[$key] .= ' - ' . $set['description'];
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function listOptions(?array $allowed_iconset = NULL): array {
    $icons = $this->getIcons();

    if (empty($icons)) {
      return [];
    }

    $result = [];
    foreach ($icons as $icon_id => $icon) {
      if ($allowed_iconset) {
        if (in_array($icon->getIconsetId(), $allowed_iconset)) {
          $result[$icon_id] = sprintf('%s (%s)', $icon->getName(), $icon->getIconsetLabel());
        }
      }
      else {
        $result[$icon_id] = sprintf('%s (%s)', $icon->getName(), $icon->getIconsetLabel());
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtractorAllFormDefaults(): array {
    $all_iconset = $this->getCleanDefinitions();

    $default = [];
    foreach ($all_iconset as $iconset_id => $iconset_definition) {
      if (!isset($iconset_definition['options'])) {
        continue;
      }
      foreach ($iconset_definition['options'] as $name => $definition) {
        if (isset($definition['default'])) {
          $default[$iconset_id][$name] = $definition['default'];
        }
      }
    }

    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtractorFormDefaults(string $iconset): array {
    $all_iconset = $this->getCleanDefinitions();

    if (!isset($all_iconset[$iconset]) || !isset($all_iconset[$iconset]['options'])) {
      return [];
    }

    $default = [];
    foreach ($all_iconset[$iconset]['options'] as $name => $definition) {
      if (isset($definition['default'])) {
        $default[$name] = $definition['default'];
      }
    }

    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions(): ?array {
    $definitions = $this->getCachedDefinitions();
    if (!isset($definitions)) {
      $definitions = $this->findDefinitions();
      foreach ($definitions as $key => $definition) {
        if (isset($definition['enabled']) && $definition['enabled'] === FALSE) {
          unset($definitions[$key]);
        }
      }
      $this->setCachedDefinitions($definitions);
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtractorPluginForms(array &$form, FormStateInterface $form_state, array $default_settings = [], array $allowed_iconset = [], bool $details = FALSE): void {
    $iconset = $this->getCleanDefinitions();
    if (!empty($allowed_iconset)) {
      $iconset = array_intersect_key($iconset, $allowed_iconset);
    }

    $extractor_forms = $this->iconsetExtractorManager->getExtractorForms($iconset);
    if (empty($extractor_forms)) {
      return;
    }

    foreach ($iconset as $iconset_id => $plugin) {
      $extractor_id = $plugin['extractor'];

      // Create the container for each extractor settings used to have the
      // extractor form.
      $form[$iconset_id] = [
        '#type' => $details ? 'details' : 'container',
        '#title' => $plugin['label'],
        // Name is used for js hide/show settings.
        // @see web/modules/ui_icons/js/ui_icons.admin.js
        '#attributes' => ['name' => 'icon-settings--' . $iconset_id],
      ];

      // Create the extractor form and set settings so we can build with values.
      $subform_state = SubformState::createForSubform($form[$iconset_id], $form, $form_state);
      $subform_state->getCompleteFormState()->setValue('saved_values', $default_settings[$iconset_id][$extractor_id] ?? []);
      if (is_a($extractor_forms[$extractor_id], '\Drupal\Core\Plugin\PluginFormInterface')) {
        $form[$iconset_id][$extractor_id] = $extractor_forms[$extractor_id]->buildConfigurationForm($form[$iconset_id], $subform_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery(): DiscoveryInterface {
    if (!$this->discovery) {
      $this->discovery = new YamlDiscovery('ui_icons', $this->moduleHandler->getModuleDirectories() + $this->themeHandler->getThemeDirectories());
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id): void {
    if (preg_match('@[^a-z0-9_]@', $plugin_id)) {
      throw new IconsetConfigErrorException('Invalid Iconset id, name must contain only lowercase letters, numbers, and underscores.');
    }

    // @todo replace with json validation.
    if (!isset($definition['extractor'])) {
      throw new IconsetConfigErrorException('Missing `extractor:` key in your definition!');
    }
    // @todo is it needed as a extractor plugin can exist without config key.
    if (!isset($definition['config'])) {
      throw new IconsetConfigErrorException('Missing `config:` key in your definition extractor!');
    }

    $relative_path = $this->moduleHandler->moduleExists($definition['provider'])
    ? $this->moduleHandler->getModule($definition['provider'])->getPath()
    : $this->themeHandler->getTheme($definition['provider'])->getPath();

    // Provide path information for extractor.
    $definition += [
      '_path_info' => [
        'drupal_root' => $this->appRoot,
        'absolute_path' => sprintf('%s/%s', $this->appRoot, $relative_path),
        'relative_path' => $relative_path,
      ],
      'iconset_id' => $definition['id'],
      'iconset_label' => $definition['label'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider): bool {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * Return definitions without cached loaded icons.
   */
  private function getCleanDefinitions(): array {
    $definitions = $this->getDefinitions();
    unset($definitions['_icons_loaded']);
    return $definitions;
  }

}
