<?php

declare(strict_types=1);

namespace Drupal\unirate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin configuration form for UniRate Currency.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'unirate_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['unirate.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('unirate.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('Your UniRate API key from <a href="https://unirateapi.com">unirateapi.com</a>. You can also set the <code>UNIRATE_API_KEY</code> environment variable.'),
      '#default_value' => $config->get('api_key'),
      '#required' => FALSE,
    ];

    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API base URL'),
      '#description' => $this->t('Override only for testing. Leave blank to use the default: <code>https://api.unirateapi.com</code>.'),
      '#default_value' => $config->get('base_url'),
      '#placeholder' => 'https://api.unirateapi.com',
    ];

    $form['timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Request timeout (seconds)'),
      '#description' => $this->t('Maximum seconds to wait for the UniRate API to respond.'),
      '#default_value' => $config->get('timeout') ?: 10,
      '#min' => 1,
      '#max' => 60,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('unirate.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('base_url', $form_state->getValue('base_url') ?: 'https://api.unirateapi.com')
      ->set('timeout', (int) $form_state->getValue('timeout'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
