<?php

namespace Drupal\wwsauth\Plugin\OpenIDConnectClient;

use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;
use Drupal\user\Entity\Role;

/**
 * WWS OpenID Connect client.
 *
 * Implements OpenID Connect Client plugin for WWS.
 *
 * @OpenIDConnectClient(
 *   id = "WWSUmich",
 *   label = @Translation("Wolverine Web Services")
 * )
 */
class OpenIDConnectUmichClient extends OpenIDConnectClientBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
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
      '#description' => 'An OIDC managed role name must match an m-community group name. Roles selected here will be managed by the OIDC login process and not manually assignable.',
    ];
    $form['testshib'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use testing instance of the IDP'),
      '#description' => 'Only check this box if directed to do so by ITS',
      '#default_value' => $this->configuration['testshib'],
    ];
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function getEndpoints() {
    if ($this->configuration['testshib'] == 1) {
      $service = json_decode(file_get_contents("https://shib-idp-staging.dsc.umich.edu/.well-known/openid-configuration"));

    }
    else {
      $service = json_decode(file_get_contents("https://shibboleth.umich.edu/.well-known/openid-configuration"));
    }
    return [
      'authorization' => $service->authorization_endpoint,
      'token' => $service->token_endpoint,
      'userinfo' => $service->userinfo_endpoint,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function authorize($scope = '') {
    return parent::authorize('openid email edumember profile  account_type');
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
