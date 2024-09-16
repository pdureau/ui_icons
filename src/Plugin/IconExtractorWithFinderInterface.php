<?php

declare(strict_types=1);

namespace Drupal\ui_icons\Plugin;

/**
 * Interface for ui_icons_extractor plugins.
 */
interface IconExtractorWithFinderInterface extends IconExtractorInterface {

  /**
   * Create files from sources config.
   *
   * @param array $sources
   *   The extractor config sources, path or url.
   * @param array $paths
   *   The definition paths. Include:
   *   - drupal_root
   *   - absolute_path
   *   - relative_path.
   *
   * @return array
   *   List of files with metadata.
   */
  public function getFilesFromSources(array $sources, array $paths): array;

}
