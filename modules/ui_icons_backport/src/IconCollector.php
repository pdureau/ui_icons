<?php

declare(strict_types=1);

namespace Drupal\ui_icons_backport\Icon;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * A CacheCollector implementation for building icons info.
 */
class IconCollector extends CacheCollector {

  /**
   * Constructs a IconCollector instance.
   *
   * @param \Drupal\Core\Theme\Icon\IconExtractorPluginManager $iconPackExtractorManager
   *   The icon plugin extractor service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   */
  public function __construct(
    protected readonly IconExtractorPluginManager $iconPackExtractorManager,
    CacheBackendInterface $cache,
    LockBackendInterface $lock,
  ) {
    parent::__construct('icon_info', $cache, $lock, ['icon_pack_collector']);
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value): void {
    parent::set($key, $value);
    $this->persist($key);
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, array $definition = []): ?IconDefinitionInterface {
    $this->lazyLoadCache();
    if (\array_key_exists($key, $this->storage)) {
      return $this->storage[$key];
    }
    else {
      return $this->resolveCacheMiss($key, $definition);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resolveCacheMiss($key, array $definition = []): ?IconDefinitionInterface {
    $this->storage[$key] = $this->getIconFromExtractor($key, $definition);
    $this->persist($key);

    return $this->storage[$key];
  }

  /**
   * Returns the icon from an icon id and icon pack definition.
   *
   * @param string $icon_full_id
   *   The icon full id as pack_id:icon_id.
   * @param array $definitions
   *   The icon pack definitions.
   *
   * @return \Drupal\Core\Theme\Icon\IconDefinitionInterface|null
   *   The icon loaded.
   */
  private function getIconFromExtractor(string $icon_full_id, array $definitions): ?IconDefinitionInterface {
    $icon_data = IconDefinition::getIconDataFromId($icon_full_id);
    $definition = $definitions[$icon_data['pack_id']] ?? NULL;
    if (NULL === $definition) {
      return NULL;
    }

    $icon_definition = $definition['icons'][$icon_full_id] ?? NULL;
    if (NULL === $icon_definition) {
      return NULL;
    }

    $icon_definition['icon_id'] = $icon_data['icon_id'];

    /** @var \Drupal\Core\Theme\Icon\IconExtractorInterface $extractor */
    $extractor = $this->iconPackExtractorManager->createInstance($definition['extractor'], $definition);
    return $extractor->loadIcon($icon_definition);
  }

}