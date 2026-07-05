<?php

declare(strict_types=1);

namespace Drupal\Tests\unirate\Unit;

use Drupal\unirate\UniRateClient;
use Drupal\unirate\UniRateException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;

#[CoversClass(\Drupal\unirate\UniRateClient::class)]
final class UniRateClientTest extends TestCase {

  private function makeClient(array $responses, array &$history = [], string $apiKey = 'test-key'): UniRateClient {
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));
    $guzzle = new Client(['handler' => $stack]);
    return new UniRateClient($guzzle, $apiKey);
  }

  private function makeClientWithLogger(array $responses, LoggerInterface $logger): UniRateClient {
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);
    $guzzle = new Client(['handler' => $stack]);
    return new UniRateClient($guzzle, 'test-key', UniRateClient::API_BASE_URL, 10.0, $logger);
  }

  // ── getRate ───────────────────────────────────────────────────────────────

  public function testGetRateSuccess(): void {
    $client = $this->makeClient([
      new Response(200, [], json_encode(['rate' => 1.0856])),
    ]);
    self::assertSame(1.0856, $client->getRate('USD', 'EUR'));
  }

  public function testGetRateReturnsFloat(): void {
    $client = $this->makeClient([
      new Response(200, [], json_encode(['rate' => '1.2'])),
    ]);
    self::assertIsFloat($client->getRate('USD', 'GBP'));
  }

  public function testGetRateMissingKey(): void {
    $client = $this->makeClient([
      new Response(200, [], json_encode(['other' => 1.0])),
    ]);
    $this->expectException(UniRateException::class);
    $this->expectExceptionMessage('"rate"');
    $client->getRate('USD', 'EUR');
  }

  public function testGetRateSendsFromTo(): void {
    $history = [];
    $client = $this->makeClient([
      new Response(200, [], json_encode(['rate' => 1.1])),
    ], $history);
    $client->getRate('GBP', 'JPY');
    $uri = (string) $history[0]['request']->getUri();
    self::assertStringContainsString('from=GBP', $uri);
    self::assertStringContainsString('to=JPY', $uri);
  }

  public function testGetRateSendsAcceptHeader(): void {
    $history = [];
    $client = $this->makeClient([
      new Response(200, [], json_encode(['rate' => 1.1])),
    ], $history);
    $client->getRate('USD', 'EUR');
    self::assertSame('application/json', $history[0]['request']->getHeaderLine('Accept'));
  }

  public function testGetRateIncludesApiKey(): void {
    $history = [];
    $client = $this->makeClient([
      new Response(200, [], json_encode(['rate' => 1.0])),
    ], $history, 'my-secret');
    $client->getRate('USD', 'EUR');
    self::assertStringContainsString('api_key=my-secret', (string) $history[0]['request']->getUri());
  }

  // ── getRates ──────────────────────────────────────────────────────────────

  public function testGetRatesSuccess(): void {
    $payload = ['rates' => ['EUR' => 0.92, 'GBP' => 0.79]];
    $client = $this->makeClient([new Response(200, [], json_encode($payload))]);
    self::assertSame(['EUR' => 0.92, 'GBP' => 0.79], $client->getRates('USD'));
  }

  public function testGetRatesMissingKey(): void {
    $client = $this->makeClient([new Response(200, [], json_encode(['other' => []]))]);
    $this->expectException(UniRateException::class);
    $this->expectExceptionMessage('"rates"');
    $client->getRates('USD');
  }

  public function testGetRatesSendsBase(): void {
    $history = [];
    $client = $this->makeClient([
      new Response(200, [], json_encode(['rates' => ['EUR' => 0.9]])),
    ], $history);
    $client->getRates('GBP');
    self::assertStringContainsString('base=GBP', (string) $history[0]['request']->getUri());
  }

  // ── convert ───────────────────────────────────────────────────────────────

  public function testConvertSuccess(): void {
    $client = $this->makeClient([
      new Response(200, [], json_encode(['converted_amount' => 92.50])),
    ]);
    self::assertSame(92.50, $client->convert(100.0, 'USD', 'EUR'));
  }

  public function testConvertMissingKey(): void {
    $client = $this->makeClient([new Response(200, [], json_encode(['other' => 1.0]))]);
    $this->expectException(UniRateException::class);
    $this->expectExceptionMessage('"converted_amount"');
    $client->convert(100.0, 'USD', 'EUR');
  }

  public function testConvertSendsParams(): void {
    $history = [];
    $client = $this->makeClient([
      new Response(200, [], json_encode(['converted_amount' => 50.0])),
    ], $history);
    $client->convert(50.0, 'EUR', 'GBP');
    $uri = (string) $history[0]['request']->getUri();
    self::assertStringContainsString('amount=50', $uri);
    self::assertStringContainsString('from=EUR', $uri);
    self::assertStringContainsString('to=GBP', $uri);
  }

  // ── listCurrencies ────────────────────────────────────────────────────────

  public function testListCurrenciesSuccess(): void {
    $payload = ['currencies' => ['USD' => 'US Dollar', 'EUR' => 'Euro']];
    $client = $this->makeClient([new Response(200, [], json_encode($payload))]);
    self::assertSame(['USD' => 'US Dollar', 'EUR' => 'Euro'], $client->listCurrencies());
  }

  public function testListCurrenciesMissingKey(): void {
    $client = $this->makeClient([new Response(200, [], json_encode(['other' => []]))]);
    $this->expectException(UniRateException::class);
    $this->expectExceptionMessage('"currencies"');
    $client->listCurrencies();
  }

  public function testListCurrenciesHitsCorrectPath(): void {
    $history = [];
    $client = $this->makeClient([
      new Response(200, [], json_encode(['currencies' => []])),
    ], $history);
    $client->listCurrencies();
    self::assertStringContainsString('/api/currencies', (string) $history[0]['request']->getUri());
  }

  // ── error handling ────────────────────────────────────────────────────────

  public function testEmptyApiKeyThrows(): void {
    $mock = new MockHandler([new Response(200, [], '{}')]);
    $guzzle = new Client(['handler' => HandlerStack::create($mock)]);
    $client = new UniRateClient($guzzle, '');
    $this->expectException(UniRateException::class);
    $this->expectExceptionMessage('API key');
    $client->getRate('USD', 'EUR');
  }

  public function testHttp401ThrowsWithMessage(): void {
    $req = new Request('GET', '/api/rate');
    $res = new Response(401, [], '{"error":"unauthorized"}');
    $client = $this->makeClient([new ClientException('401', $req, $res)]);
    $this->expectException(UniRateException::class);
    $this->expectExceptionMessage('authentication failed');
    $client->getRate('USD', 'EUR');
  }

  public function testHttp403ThrowsProMessage(): void {
    $req = new Request('GET', '/api/rate');
    $res = new Response(403, [], '{"error":"forbidden"}');
    $client = $this->makeClient([new ClientException('403', $req, $res)]);
    $this->expectException(UniRateException::class);
    $this->expectExceptionMessage('Pro plan');
    $client->getRate('USD', 'EUR');
  }

  public function testHttp429ThrowsRateLimitMessage(): void {
    $req = new Request('GET', '/api/rate');
    $res = new Response(429, [], '');
    $client = $this->makeClient([new ClientException('429', $req, $res)]);
    $this->expectException(UniRateException::class);
    $this->expectExceptionMessage('rate limit');
    $client->getRate('USD', 'EUR');
  }

  public function testHttp500ThrowsGenericMessage(): void {
    $req = new Request('GET', '/api/rate');
    $res = new Response(500, [], '');
    $client = $this->makeClient([new ServerException('500', $req, $res)]);
    $this->expectException(UniRateException::class);
    $this->expectExceptionMessage('HTTP 500');
    $client->getRate('USD', 'EUR');
  }

  public function testNetworkErrorThrows(): void {
    $req = new Request('GET', '/api/rate');
    $client = $this->makeClient([new ConnectException('timeout', $req)]);
    $this->expectException(UniRateException::class);
    $this->expectExceptionMessage('request failed');
    $client->getRate('USD', 'EUR');
  }

  public function testInvalidJsonThrows(): void {
    $client = $this->makeClient([new Response(200, [], 'not-json{{{')]);
    $this->expectException(UniRateException::class);
    $this->expectExceptionMessage('invalid JSON');
    $client->getRate('USD', 'EUR');
  }

  public function testNonObjectJsonThrows(): void {
    $client = $this->makeClient([new Response(200, [], '"just a string"')]);
    $this->expectException(UniRateException::class);
    $client->getRate('USD', 'EUR');
  }

  public function testLoggerCalledOnHttpError(): void {
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects(self::once())->method('error');

    $req = new Request('GET', '/api/rate');
    $res = new Response(401, [], '');
    $client = $this->makeClientWithLogger([new ClientException('401', $req, $res)], $logger);

    try {
      $client->getRate('USD', 'EUR');
    }
    catch (UniRateException) {}
  }

  public function testLoggerCalledOnNetworkError(): void {
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects(self::once())->method('error');

    $req = new Request('GET', '/api/rate');
    $client = $this->makeClientWithLogger([new ConnectException('timeout', $req)], $logger);

    try {
      $client->getRate('USD', 'EUR');
    }
    catch (UniRateException) {}
  }

  public function testCustomBaseUrl(): void {
    $history = [];
    $mock = new MockHandler([new Response(200, [], json_encode(['rate' => 1.0]))]);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));
    $guzzle = new Client(['handler' => $stack]);
    $client = new UniRateClient($guzzle, 'key', 'https://mock.local');

    $client->getRate('USD', 'EUR');
    self::assertStringStartsWith('https://mock.local', (string) $history[0]['request']->getUri());
  }

}
