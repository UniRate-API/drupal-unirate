# UniRate Currency — Drupal module

[![CI](https://github.com/UniRate-API/drupal-unirate/actions/workflows/ci.yml/badge.svg)](https://github.com/UniRate-API/drupal-unirate/actions/workflows/ci.yml)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://www.php.net/)
[![Drupal 10.3 | 11](https://img.shields.io/badge/Drupal-10.3%20|%2011-blue)](https://www.drupal.org/)

Drupal module providing live currency exchange rates from the [UniRate API](https://unirateapi.com). Designed to integrate cleanly into any Drupal 10/11 project with zero additional runtime dependencies.

## Features

- Injectable **`unirate.client`** service — use UniRate data anywhere in your Drupal code
- **Currency Rate Block** — display a live exchange rate in any block region
- **Admin settings** at `/admin/config/services/unirate-currency` — store your API key in Drupal config (or use the `UNIRATE_API_KEY` environment variable)
- Zero runtime PHP dependencies beyond Drupal core (uses Drupal's built-in Guzzle HTTP client)
- 31 PHPUnit unit tests

## Installation

Install via Composer from GitHub:

```bash
composer require unirate-api/unirate-drupal
drush en unirate
```

Then set your API key at `/admin/config/services/unirate-currency` or via the environment variable `UNIRATE_API_KEY`.

Get a free API key at [unirateapi.com](https://unirateapi.com).

## Usage

### Inject the service

```php
use Drupal\unirate\UniRateClient;
use Drupal\unirate\UniRateException;

class MyCurrencyService {
  public function __construct(
    private UniRateClient $uniRate,
  ) {}

  public function getUsdToEur(): float {
    return $this->uniRate->getRate('USD', 'EUR');
  }

  public function convertAmount(float $amount, string $from, string $to): float {
    return $this->uniRate->convert($amount, $from, $to);
  }
}
```

Register it in your `services.yml`:

```yaml
my_module.currency_service:
  class: Drupal\my_module\MyCurrencyService
  arguments:
    - '@unirate.client'
```

### Available methods

```php
// Get a single rate
$rate = $client->getRate('USD', 'EUR');   // float

// Get all rates relative to a base
$rates = $client->getRates('USD');        // array<string, float>

// Convert an amount
$eur = $client->convert(100.0, 'USD', 'EUR'); // float

// List supported currencies
$currencies = $client->listCurrencies();  // array<string, string>
```

All methods throw `UniRateException` on error (network failure, invalid API key, Pro-gated endpoint on a free plan, rate limit, etc.).

### Use in Twig via Drupal services

```twig
{% set rate = drupal_token('unirate:rate:USD:EUR') %}
```

Or place the **UniRate: Currency Rate** block via the block layout interface.

## Configuration

| Setting | Config key | Environment variable | Default |
|---|---|---|---|
| API key | `unirate.settings:api_key` | `UNIRATE_API_KEY` | — |
| Base URL | `unirate.settings:base_url` | — | `https://api.unirateapi.com` |
| Timeout | `unirate.settings:timeout` | — | 10 seconds |

## Drupal.org submission

The module is pending drupal.org project namespace approval. Until then, install directly via the Composer repository above.

---

<!-- unirate-ecosystem-start -->
## Other UniRate integrations

| Language / framework | Package |
|---|---|
| Python | [`unirate-api`](https://github.com/UniRate-API/unirate-api-python) |
| Node.js | [`unirate-api`](https://github.com/UniRate-API/unirate-api-nodejs) |
| Go | [`unirate-api-go`](https://github.com/UniRate-API/unirate-api-go) |
| Rust | [`unirate-api`](https://github.com/UniRate-API/unirate-api-rust) |
| Ruby | [`unirate-api`](https://github.com/UniRate-API/unirate-api-ruby) |
| PHP | [`unirate-api`](https://github.com/UniRate-API/unirate-api-php) |
| Java | [`unirate-api-java`](https://github.com/UniRate-API/unirate-api-java) |
| Swift | [`unirate-api-swift`](https://github.com/UniRate-API/unirate-api-swift) |
| .NET | [`UniRateApi`](https://github.com/UniRate-API/unirate-api-dotnet) |
| React | [`@unirate/react`](https://github.com/UniRate-API/react-unirate) |
| Next.js | [`@unirate/next`](https://github.com/UniRate-API/next-unirate) |
| Vue | [`@unirate/vue`](https://github.com/UniRate-API/vue-unirate) |
| Angular | [`@unirate/angular`](https://github.com/UniRate-API/angular-unirate) |
| Svelte | [`@unirate/sveltekit`](https://github.com/UniRate-API/sveltekit-unirate) |
| Nuxt | [`@unirate/nuxt`](https://github.com/UniRate-API/nuxt-unirate) |
| Remix | [`@unirate/remix`](https://github.com/UniRate-API/remix-unirate) |
| NestJS | [`@unirate/nestjs`](https://github.com/UniRate-API/nestjs-unirate) |
| Astro | [`@unirate/astro`](https://github.com/UniRate-API/astro-unirate) |
| Eleventy | [`@unirate/eleventy`](https://github.com/UniRate-API/eleventy-unirate) |
| Hugo | [`hugo-unirate`](https://github.com/UniRate-API/hugo-unirate) |
| Jekyll | [`jekyll-unirate`](https://github.com/UniRate-API/jekyll-unirate) |
| Django | [`djangorestframework-unirate`](https://github.com/UniRate-API/djangorestframework-unirate) |
| FastAPI | [`fastapi-unirate`](https://github.com/UniRate-API/fastapi-unirate) |
| Flask | [`flask-unirate`](https://github.com/UniRate-API/flask-unirate) |
| Wagtail | [`wagtail-unirate`](https://github.com/UniRate-API/wagtail-unirate) |
| Laravel | [`unirate-api/laravel-money`](https://github.com/UniRate-API/laravel-money-unirate) |
| Symfony | [`unirate-api/unirate-bundle`](https://github.com/UniRate-API/unirate-bundle) |
| Strapi | [`strapi-plugin-unirate`](https://github.com/UniRate-API/strapi-plugin-unirate) |
| WordPress | [`unirate-currency-converter`](https://github.com/UniRate-API/unirate-currency-converter) |
| LangChain (Python) | [`langchain-unirate`](https://github.com/UniRate-API/langchain-unirate) |
| LangChain (JS) | [`@unirate/langchain-js`](https://github.com/UniRate-API/langchain-js-unirate) |
| MCP server | [`@unirate/mcp`](https://github.com/UniRate-API/unirate-mcp) |
| CLI | [`unirate-cli`](https://github.com/UniRate-API/unirate-cli) |
<!-- unirate-ecosystem-end -->
