<?php

declare(strict_types=1);

namespace Drupal\unirate\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\unirate\UniRateClient;
use Drupal\unirate\UniRateException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays a live currency exchange rate.
 */
#[Block(
  id: 'unirate_currency_rate',
  admin_label: new TranslatableMarkup('UniRate: Currency Rate'),
  category: new TranslatableMarkup('UniRate'),
)]
class CurrencyRateBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    private readonly UniRateClient $uniRateClient,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('unirate.client'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'from' => 'USD',
      'to' => 'EUR',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(array $form, FormStateInterface $form_state): array {
    $form['from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From currency'),
      '#default_value' => $this->configuration['from'],
      '#size' => 5,
      '#maxlength' => 3,
      '#required' => TRUE,
    ];

    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To currency'),
      '#default_value' => $this->configuration['to'],
      '#size' => 5,
      '#maxlength' => 3,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit(array $form, FormStateInterface $form_state): void {
    $this->configuration['from'] = strtoupper($form_state->getValue('from'));
    $this->configuration['to'] = strtoupper($form_state->getValue('to'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $from = $this->configuration['from'] ?? 'USD';
    $to = $this->configuration['to'] ?? 'EUR';

    try {
      $rate = $this->uniRateClient->getRate($from, $to);

      return [
        '#theme' => 'unirate_currency_rate',
        '#from' => $from,
        '#to' => $to,
        '#rate' => $rate,
        '#cache' => ['max-age' => 3600],
      ];
    }
    catch (UniRateException $e) {
      return [
        '#theme' => 'unirate_currency_rate',
        '#from' => $from,
        '#to' => $to,
        '#error' => $e->getMessage(),
        '#cache' => ['max-age' => 0],
      ];
    }
  }

}
