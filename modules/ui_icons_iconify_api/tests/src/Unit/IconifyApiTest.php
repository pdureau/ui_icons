<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_icons_iconify_api\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\UnitTestCase;
use Drupal\ui_icons_iconify_api\IconifyApi;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for the Iconify API service.
 *
 * @group ui_icons
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 */
class IconifyApiTest extends UnitTestCase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The Iconify API service.
   *
   * @var \Drupal\ui_icons_iconify_api\IconifyApi
   */
  protected $iconifyApi;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->iconifyApi = new IconifyApi($this->httpClient, $this->logger);
  }

  public function testGetIconsByCollectionSuccess(): void {
    $collection = 'test-collection';
    $response_data = [
      'uncategorized' => ['icon-1', 'icon-2'],
    ];

    $response = new Response(200, [], Json::encode($response_data));

    $this->httpClient
      ->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        IconifyApi::COLLECTION_API_ENDPOINT,
        ['query' => ['prefix' => $collection]]
      )
      ->willReturn($response);

    $icons = $this->iconifyApi->getIconsByCollection($collection);

    $this->assertEquals(['icon-1', 'icon-2'], $icons);
  }

  public function testGetIconsByCollectionCategoriesSuccess(): void {
    $collection = 'test-collection';
    $response_data = [
      'categories' => [
        ['icon-1', 'icon-2'],
        ['icon-3', 'icon-4'],
      ],
      'uncategorized' => ['icon-5', 'icon-6'],
    ];

    $response = new Response(200, [], Json::encode($response_data));

    $this->httpClient
      ->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        IconifyApi::COLLECTION_API_ENDPOINT,
        ['query' => ['prefix' => $collection]]
      )
      ->willReturn($response);

    $icons = $this->iconifyApi->getIconsByCollection($collection);

    $this->assertEquals(['icon-1', 'icon-2', 'icon-3', 'icon-4'], $icons);
  }

  public function testGetIconsByCollectionInvalidResponse(): void {
    $collection = 'test-collection';
    $response = new Response(200, [], 'invalid-json');

    $this->httpClient
      ->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $this->logger
      ->expects($this->once())
      ->method('error')
      ->with(
        'Iconify error for @collection: @error',
        [
          '@collection' => $collection,
          '@error' => 'invalid-json',
        ]
      );

    $icons = $this->iconifyApi->getIconsByCollection($collection);

    $this->assertEquals([], $icons);
  }

  public function testGetIconsByCollectionClientException(): void {
    $collection = 'test-collection';
    $this->httpClient
      ->expects($this->once())
      ->method('request')
      ->willThrowException(new ClientException('Client exception', $this->createMock(RequestInterface::class),
    $this->createMock(ResponseInterface::class)));

    $this->logger
      ->expects($this->once())
      ->method('error');

    $icons = $this->iconifyApi->getIconsByCollection($collection);
    $this->assertEquals([], $icons);
  }

  public function testGetIconsByCollectionServerException(): void {
    $collection = 'test-collection';
    $this->httpClient
      ->expects($this->once())
      ->method('request')
      ->willThrowException(new ServerException('Server exception', $this->createMock(RequestInterface::class),
    $this->createMock(ResponseInterface::class)));

    $this->logger
      ->expects($this->once())
      ->method('error');

    $icons = $this->iconifyApi->getIconsByCollection($collection);
    $this->assertEquals([], $icons);
  }

}
