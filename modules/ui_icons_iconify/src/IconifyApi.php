<?php

declare(strict_types=1);

namespace Drupal\ui_icons_iconify;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;

/**
 * Service for Iconify API functionality.
 */
class IconifyApi implements IconifyApiInterface {

  public const API_ENDPOINT = 'https://api.iconify.design';
  public const COLLECTION_API_ENDPOINT = 'https://api.iconify.design/collection';

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getIconsByCollection(string $collection): array {
    try {
      $response = $this->httpClient->request(
        'GET',
        $this::COLLECTION_API_ENDPOINT,
        ['query' => ['prefix' => $collection]]
      );

      $icons_list = Json::decode((string) $response->getBody());

      if (!is_array($icons_list)) {
        $param = [
          '@collection' => $collection,
          '@error' => (string) $response->getBody(),
        ];
        $this->logger->error('Iconify error for @collection: @error', $param);
        return [];
      }

      $icons = [];
      if (isset($icons_list['categories']) && !empty($icons_list['categories'])) {
        foreach ($icons_list['categories'] as $list) {
          $icons = array_merge($icons, $list);
        }

        return $icons;
      }

      return $icons_list['uncategorized'] ?? [];
    }
    catch (ClientException | ServerException $e) {
      $param = [
        '@errorType' => ($e instanceof ClientException) ? 'client' : 'server',
        '@collection' => $collection,
        '@error' => $e->getResponse(),
      ];
      $this->logger->error(
        'Iconify @errorType error for @collection: @error',
        $param
      );
      return [];
    }

  }

}
