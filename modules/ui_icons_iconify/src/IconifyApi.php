<?php

declare(strict_types=1);

namespace Drupal\ui_icons_iconify;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;

/**
 * Service for Iconify functionality.
 */
class IconifyApi implements IconifyApiInterface {

  public const API_ENDPOINT = 'https://api.iconify.design';
  private const COLLECTION_API_ENDPOINT = 'https://api.iconify.design/collection';

  /**
   * IconifyApi constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getIconsByCollection(string $collection): array {
    $request_options = [
      'query' => [
        'prefix' => $collection,
      ],
    ];

    try {
      $response = $this->httpClient->request('GET', $this::COLLECTION_API_ENDPOINT, $request_options);
    }
    catch (ClientException | ServerException $e) {
      $errorType = $e instanceof ClientException ? 'client' : 'server';
      $param = ['@collection' => $collection, '@error' => $e->getResponse()];
      $this->logger->error('Iconify ' . $errorType . ' error for @collection: @error', $param);
      return [];
    }

    $icons_list = Json::decode((string) $response->getBody());

    if (!is_array($icons_list)) {
      $param = ['@collection' => $collection, '@error' => (string) $response->getBody()];
      $this->logger->error('Iconify error for @collection: @error', $param);
      return [];
    }

    $icons = [];
    if (isset($icons_list['categories']) && !empty($icons_list['categories'])) {
      foreach ($icons_list['categories'] as $list) {
        $icons = [...$icons, ...$list];
      }

      return $icons;
    }

    return $icons_list['uncategorized'] ?? [];
  }

}
