<?php

declare(strict_types=1);

namespace Drupal\unirate;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Client for the UniRate API.
 *
 * Accepts the Guzzle client directly so it is unit-testable with MockHandler
 * without a Drupal bootstrap. Instantiated via UniRateClientFactory in the
 * service container.
 */
class UniRateClient {

  public const API_BASE_URL = 'https://api.unirateapi.com';

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly string $apiKey,
    private readonly string $baseUrl = self::API_BASE_URL,
    private readonly float $timeout = 10.0,
    private readonly LoggerInterface $logger = new NullLogger(),
  ) {}

  /**
   * Returns the spot rate from $from to $to.
   *
   * @throws UniRateException
   */
  public function getRate(string $from, string $to): float {
    $data = $this->request('/api/rate', ['from' => $from, 'to' => $to]);
    if (!isset($data['rate'])) {
      throw new UniRateException('Missing "rate" key in UniRate API response.');
    }
    return (float) $data['rate'];
  }

  /**
   * Returns all rates relative to $base.
   *
   * @return array<string, float>
   *
   * @throws UniRateException
   */
  public function getRates(string $base): array {
    $data = $this->request('/api/rates', ['base' => $base]);
    if (!isset($data['rates'])) {
      throw new UniRateException('Missing "rates" key in UniRate API response.');
    }
    return (array) $data['rates'];
  }

  /**
   * Converts $amount from $from to $to.
   *
   * @throws UniRateException
   */
  public function convert(float $amount, string $from, string $to): float {
    $data = $this->request('/api/convert', [
      'amount' => $amount,
      'from' => $from,
      'to' => $to,
    ]);
    if (!isset($data['converted_amount'])) {
      throw new UniRateException('Missing "converted_amount" key in UniRate API response.');
    }
    return (float) $data['converted_amount'];
  }

  /**
   * Returns the list of supported currency codes and names.
   *
   * @return array<string, string>
   *
   * @throws UniRateException
   */
  public function listCurrencies(): array {
    $data = $this->request('/api/currencies');
    if (!isset($data['currencies'])) {
      throw new UniRateException('Missing "currencies" key in UniRate API response.');
    }
    return (array) $data['currencies'];
  }

  /**
   * Makes a GET request and returns the decoded JSON body.
   *
   * @param array<string, mixed> $query
   *
   * @return array<string, mixed>
   *
   * @throws UniRateException
   */
  private function request(string $path, array $query = []): array {
    if ($this->apiKey === '') {
      throw new UniRateException(
        'UniRate API key is not configured. Set it at /admin/config/services/unirate-currency or via the UNIRATE_API_KEY environment variable.'
      );
    }

    $query['api_key'] = $this->apiKey;

    try {
      $response = $this->httpClient->request('GET', $this->baseUrl . $path, [
        'headers' => ['Accept' => 'application/json'],
        'query' => $query,
        'timeout' => $this->timeout,
        'http_errors' => true,
      ]);

      $body = (string) $response->getBody();
      $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

      if (!is_array($data)) {
        throw new UniRateException('UniRate API returned a non-object response.');
      }

      return $data;
    }
    catch (BadResponseException $e) {
      $status = $e->getResponse()->getStatusCode();
      $message = match ($status) {
        401 => 'UniRate API authentication failed — check your API key.',
        403 => 'UniRate API access denied — this endpoint requires a Pro plan.',
        429 => 'UniRate API rate limit exceeded.',
        default => sprintf('UniRate API returned HTTP %d.', $status),
      };
      $this->logger->error('@message', ['@message' => $message]);
      throw new UniRateException($message, $status, $e);
    }
    catch (GuzzleException $e) {
      $message = 'UniRate API request failed: ' . $e->getMessage();
      $this->logger->error('@message', ['@message' => $message]);
      throw new UniRateException($message, 0, $e);
    }
    catch (\JsonException $e) {
      throw new UniRateException('UniRate API returned invalid JSON: ' . $e->getMessage(), 0, $e);
    }
  }

}
