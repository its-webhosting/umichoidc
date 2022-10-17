<?php

use Drupal\Core\Form\FormStateInterface;

/**
 * An openid_connect plugin for authenticating via Wolverine Web Services.
 */
class OpenIDConnectUmichClient extends OpenIDConnectClientBase {

  /**
   * {@inheritdoc}.
   */
  public function settingsForm() {
    $form = parent::settingsForm();
    $role_list = array_filter(user_roles(), function($role) {
      return !in_array($role, ['anonymous user', 'authenticated user', 'administrator']);
    });

    $form['roles'] = [
      '#type' => 'select',
      '#title' => t('OIDC managed Roles'),
      '#options' => $role_list,
      '#default_value' => $this->getSetting('roles'),
      '#multiple' => TRUE,
      '#description' => 'An OIDC managed role name must match an m-community group name. Roles selected here will be managed by the OIDC login process and not manually assignable.',
    ];
    $form['testshib'] = [
      '#type' => 'checkbox',
      '#title' => t('Use testing instance of the IDP'),
      '#description' => 'Only check this box if directed to do so by ITS',
      '#default_value' => $this->getSetting('testshib'),
    ];
    $form['link_openid_on_login_form'] = [
      '#type' => 'checkbox',
      '#title' => t('Add to standard login form'),
      '#default_value' => is_null($this->getSetting('link_openid_on_login_form')) ? TRUE : $this->getSetting('link_openid_on_login_form'),
      '#description' => t('The standard login form will include a link to the open id login form.'),
    ];
    $form['redirect'] = [
      '#markup' => '<b>Redirect URL:</b> (base path)/' . OPENID_CONNECT_REDIRECT_PATH_BASE . '/umichoidc',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoints() {
    if ($this->getSetting('umichoidc_testshib') == 1) {
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
    parent::authorize('openid email edumember profile  account_type');
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
  public function settingsFormSubmit($form, &$form_state) {
    if (isset($form_state['values']['client_id'])) {
      $client_id = $form_state['values']['client_id'];
      $form_state['values']['client_id'] = preg_replace( "/\r|\n/", "", trim($client_id));
    }
    if (isset($form_state['values']['client_secret'])) {
      $client_secret = $form_state['values']['client_secret'];
      $form_state['values']['client_secret'] = preg_replace( "/\r|\n/", "", trim($client_secret));
    }
  }

}
