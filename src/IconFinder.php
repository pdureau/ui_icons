<?php

declare(strict_types=1);

namespace Drupal\ui_icons;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * UI Icons finder for icon files under specific paths or urls.
 *
 * Handle our `sources` format to describe paths or urls, for paths:
 * Will search files with specific extension and extract `icon_id` and optional
 * `group` if set.
 * The `group` can be anywhere in the path and the `icon_id` can be a part of
 * the file name.
 * The result will include relative and absolute paths to the icon.
 * If the source start with a slash, `/`, path will be relative to the Drupal
 * installation, if not it will be relative to the definition folder.
 * The result Icon definition will be passed to the Extractor to prepare the
 * Icon to be returned as renderable.
 *
 * For urls the source will be the direct url to the resource.
 */
class IconFinder implements ContainerInjectionInterface, IconFinderInterface {

  use AutowireTrait;

  private const GROUP_PATTERN = '{group}';
  private const ICON_ID_PATTERN = '{icon_id}';

  public function __construct(
    private readonly FileSystemInterface $fileSystem,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function fileUrlGenerateString(string $uri): string {
    return $this->fileUrlGenerator->generateString($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function getFileContents(string $uri): string {
    return \file_get_contents($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function getFilesFromSource(string $source, string $drupal_root, string $definition_absolute_path, string $definition_relative_path): array {
    if (FALSE !== filter_var($source, FILTER_VALIDATE_URL)) {
      return $this->getFilesFromHttpUrl($source);
    }

    return $this->getFilesFromLocalPath($source, $drupal_root, $definition_absolute_path, $definition_relative_path);
  }

  /**
   * Get files from a local path.
   *
   * @param string $source
   *   The path or url.
   * @param string $drupal_root
   *   The Drupal root.
   * @param string $definition_absolute_path
   *   The current definition absolute path.
   * @param string $definition_relative_path
   *   The current definition relative path.
   *
   * @return array
   *   List of files with metadata.
   */
  private function getFilesFromLocalPath(string $source, string $drupal_root, string $definition_absolute_path, string $definition_relative_path): array {
    $is_absolute = str_starts_with($source, '/');
    $path_info = pathinfo($source);

    $group_position_end = TRUE;
    $group_position = strpos($path_info['dirname'], self::GROUP_PATTERN);
    $has_group = $group_position !== FALSE;

    if ($has_group) {
      $group_position_end = strlen($path_info['dirname']) === $group_position + strlen(self::GROUP_PATTERN);
      $path_search = rtrim(substr($path_info['dirname'], 0, $group_position), '/');
    }
    else {
      $path_search = rtrim($path_info['dirname'], '/');
    }

    $path_search = $is_absolute ? $drupal_root . $path_search : $definition_absolute_path . '/' . $path_search;

    $files = $this->findFiles($path_search, '#\\.' . $path_info['extension'] . '#', $group_position_end);

    if (empty($files)) {
      return [];
    }

    $base_relative_path = $is_absolute ? '' : $definition_relative_path . '/';

    return $this->createFileArray($files, $has_group, $group_position_end, $base_relative_path, $path_info);
  }

  /**
   * Get files from an HTTP URL.
   *
   * @param string $source
   *   The path or url.
   *
   * @return array
   *   List of files with metadata.
   */
  private function getFilesFromHttpUrl(string $source): array {
    $path_info = pathinfo($source);
    $file = (object) [
      'uri' => $source,
      'filename' => $path_info['basename'],
      'name' => $path_info['filename'],
    ];
    $files = [
      $source => $file,
    ];
    return $this->createFileArray($files, FALSE, FALSE, '', $path_info);
  }

  /**
   * Scan a directory to find files.
   *
   * @param string $dir
   *   The path to search, without group if set.
   * @param string $mask
   *   The file mask, mostly *.extension.
   * @param bool $group_position_end
   *   Flag if the group value is at the end of the path.
   *
   * @return array
   *   List of files with metadata.
   */
  private function findFiles(string $dir, string $mask, bool $group_position_end): array {
    $options = [
      'recurse' => TRUE,
      'min_depth' => $group_position_end ? 0 : 1,
    ];

    try {
      return $this->fileSystem->scanDirectory($dir, $mask, $options);
    }
    catch (\Throwable $th) {
      // @todo error missing directory?
      return [];
    }
  }

  /**
   * Create files data from files list.
   *
   * @param array $files
   *   The list of files found.
   * @param bool $has_group
   *   Flag if there is a group value in path.
   * @param bool $group_position_end
   *   Flag if the group value is at the end of the path.
   * @param string $base_relative_path
   *   The relative path of the file without group if set.
   * @param array $path_info
   *   The path info from pathinfo().
   *
   * @return array
   *   List of files with custom metadata.
   */
  private function createFileArray(array $files, bool $has_group, bool $group_position_end, string $base_relative_path, array $path_info): array {
    $result = [];

    foreach ($files as $file) {
      $group = $this->determineGroup($file->uri, $has_group, $group_position_end, $path_info['dirname']);
      $uri = $this->buildDrupalUri($has_group, $group, $path_info['dirname'], $base_relative_path, $file->filename);

      $filename = $icon_id = $file->name;
      $icon_id = $this->extractIconId($path_info['filename'], $filename);

      if (!$icon_id) {
        continue;
      }

      $result[$filename] = [
        'icon_id' => $icon_id,
        'relative_path' => $uri,
        'absolute_path' => $file->uri,
        'group' => $group,
      ];
    }

    return $result;
  }

  /**
   * Check if icon_id is a part of the name and need to be extracted.
   *
   * @param string $path_filename
   *   The path and filename to extract icon ID from.
   * @param string $filename
   *   The filename to match against.
   *
   * @return string
   *   The extracted icon ID or the original filename.
   */
  private function extractIconId(string $path_filename, string $filename): ?string {
    if ($path_filename !== self::ICON_ID_PATTERN) {
      $pattern = str_replace(self::ICON_ID_PATTERN, '(?<icon_id>.+?)', $path_filename);
      if (preg_match('@' . $pattern . '@', $filename, $matches)) {
        // @todo should return null? add test to know.
        if (isset($matches['icon_id'])) {
          return $matches['icon_id'];
        }
      }
      else {
        return NULL;
      }
    }

    return $filename;
  }

  /**
   * Builds a Drupal URI based on the provided parameters.
   *
   * @param bool $has_group
   *   Indicates if a group is present.
   * @param string $group
   *   The group value.
   * @param string $dirname
   *   The directory name.
   * @param string $base_relative_path
   *   The base relative path.
   * @param string $filename
   *   The filename to append.
   *
   * @return string
   *   The generated Drupal URI.
   */
  private function buildDrupalUri(bool $has_group, string $group, string $dirname, string $base_relative_path, string $filename): string {
    $current_relative_path = $has_group ? str_replace(self::GROUP_PATTERN, $group, $dirname) : $dirname;
    $current_relative_path = $base_relative_path . $current_relative_path;
    $current_relative_path = sprintf('%s/%s', $current_relative_path, $filename);

    return $this->fileUrlGenerateString($current_relative_path);
  }

  /**
   * Determines the group based on the URI and other parameters.
   *
   * @param string $uri
   *   The URI to extract group from.
   * @param bool $has_group
   *   Indicates if a group is present.
   * @param bool $group_position_end
   *   Indicates if the group position is at the end.
   * @param string $dirname
   *   The directory name pattern.
   *
   * @return string
   *   The determined group or empty.
   */
  private function determineGroup(string $uri, bool $has_group, bool $group_position_end, string $dirname): string {
    if (!$has_group) {
      return '';
    }

    if ($group_position_end) {
      return basename(dirname($uri));
    }

    $pattern = str_replace(self::GROUP_PATTERN, '(?P<group>.+)?', $dirname);
    preg_match('@' . $pattern . '@', $uri, $matches);
    return $matches['group'] ?? '';
  }

}
