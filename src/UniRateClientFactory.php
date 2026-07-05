<?php

declare(strict_types=1);

namespace Drupal\unirate;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Reads Drupal config and constructs a UniRateClient.
 *
 * Kept separate so UniRateClient itself has no Drupal dependency and remains
 * fully unit-testable with a plain PHPUnit + Guzzle mock setup.
 */
class UniRateClientFactory {

  public static function createFromConfig(
    ClientInterface $httpClient,
    ConfigFactoryInterface $configFactory,
    ?LoggerInterface $logger = null,
  ): UniRateClient {
    $config = $configFactory->get('unirate.settings');
    $apiKey = (string) ($config->get('api_key') ?: getenv('UNIRATE_API_KEY') ?: '');
    $baseUrl = (string) ($config->get('base_url') ?: UniRateClient::API_BASE_URL);
    $timeout = (float) ($config->get('timeout') ?: 10);

    return new UniRateClient(
      $httpClient,
      $apiKey,
      $baseUrl,
      $timeout,
      $logger ?? new NullLogger(),
    );
  }

}
