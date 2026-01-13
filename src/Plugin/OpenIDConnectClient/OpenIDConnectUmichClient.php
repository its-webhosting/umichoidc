<?php

namespace Drupal\wwsauth\Plugin\OpenIDConnectClient;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;
use Drupal\user\Entity\Role;

/**
 * WWS OpenID Connect client.
 *
 * Implements OpenID Connect Client plugin for WWS.
 *
 * @OpenIDConnectClient(
 *   id = "wwsumich",
 *   label = @Translation("Wolverine Web Services")
 * )
 */
class OpenIDConnectUmichClient extends OpenIDConnectClientBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $roles = Role::loadMultiple();
    $role_list = [];
    foreach ($roles as $i => $v) {
      if (!in_array($i, ['anonymous', 'authenticated', 'administrator', 'content_editor'])) {
        $role_list[$v->label()] = $v->label();
      }
    }
    $form['roles'] = [
      '#type' => 'select',
      '#title' => $this->t('OIDC managed Roles'),
      '#options' => $role_list,
      '#default_value' => $this->configuration['roles'],
      '#multiple' => TRUE,
      '#description' => 'An OIDC managed role name must match an MCommunity group name. Roles selected here will be managed by the OIDC login process and not manually assignable.',
    ];
    $form['oidc_well_known'] = [
      '#type' => 'url',
      '#title' => $this->t('OpenID Connect well-known endpoint URL'),
      '#default_value' => $this->configuration['oidc_well_known'],
    ];
    return $form;
  }

  /**
   * Get OpenID Connect endpoints from well-known configuration.
   *
   * @return array
   *   Array containing the authorization and token endpoints.
   */
  function getEndpoints(): array {
    // Load the well-known URL from configuration instead of hardcoding
    $well_known_url = \Drupal::config('openid_connect.client.wwsumich')
      ->get('oidc_well_known');

    // Validate the configuration value
    if (empty($well_known_url) || !is_string($well_known_url)) {
      \Drupal::logger('openid_connect')->error('OIDC well-known URL is not configured or is invalid.');
      return [];
    }

    // Trim whitespace and validate URL format
    $well_known_url = trim($well_known_url);
    if (!filter_var($well_known_url, FILTER_VALIDATE_URL)) {
      \Drupal::logger('openid_connect')->error('OIDC well-known URL is not a valid URL: @url', [
        '@url' => $well_known_url,
      ]);
      return [];
    }

    try {
      // Fetch the well-known configuration
      $client = \Drupal::httpClient();
      $response = $client->get($well_known_url);
      $data = json_decode($response->getBody()->getContents(), TRUE);

      // Validate JSON parsing
      if (json_last_error() !== JSON_ERROR_NONE) {
        \Drupal::logger('openid_connect')->error('Failed to parse OIDC well-known response: @error', [
          '@error' => json_last_error_msg(),
        ]);
        return [];
      }

      return [
        'authorization' => $data['authorization_endpoint'] ?? '',
        'token' => $data['token_endpoint'] ?? '',
        'userinfo' => $data['userinfo_endpoint'] ?? '',
      ];
    }
    catch (\Exception $e) {
      \Drupal::logger('openid_connect')->error('Failed to fetch OIDC endpoints: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function authorize(string $scope = 'openid email', array $additional_params = []): Response {
    return parent::authorize('openid email edumember profile account_type');
  }

  /**
   * {@inheritdoc}
   */
  public function decodeIdToken($id_token) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $client_id = $form_state->getValue('client_id');
    if ($client_id) {
      // Remove newlines and leading/trailing whitespace.
      $form_state->setValue('client_id', preg_replace( "/\r|\n/", "", trim($client_id)));
    }
    $client_secret = $form_state->getValue('client_secret');
    if ($client_secret) {
      // Remove newlines and leading/trailing whitespace.
      $form_state->setValue('client_secret', preg_replace( "/\r|\n/", "", trim($client_secret)));
    }
  }

}
