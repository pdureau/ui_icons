<?php

declare(strict_types=1);

namespace Drupal\ui_icons_iconify_api;

/**
 * Interface for Iconify service.
 */
interface IconifyApiInterface {

  /**
   * Gets a set of icons from a specific collection.
   *
   * @param string $collection
   *   The collection name.
   *
   * @return array
   *   The icons id list from 'uncategorized' or flat 'categories' response.
   *
   * @throws \GuzzleHttp\Exception\ClientException
   * @throws \GuzzleHttp\Exception\ServerException
   *
   * @see https://iconify.design/docs/api/collection.html
   */
  public function getIconsByCollection(string $collection): array;

}
