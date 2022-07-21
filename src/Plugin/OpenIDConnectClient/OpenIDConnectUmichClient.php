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
 *   label = @Translation("WWSUmich")
 * )
 */
class OpenIDConnectUmichClient extends OpenIDConnectClientBase
{

  /**
   *
   * @var array
   */
  protected array $userInfoMapping = [
    'name' => 'name',
    'sub' => 'id',
    'email' => 'email',
    'preferred_username' => 'login',
    'picture' => 'avatar_url',
    'profile' => 'html_url',
    'website' => 'blog',
  ];


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    $form = parent::buildConfigurationForm($form, $form_state);
    $roles = Role::loadMultiple();
    $role_list = [];
    foreach ($roles as $i => $v) {
      if (!in_array($i, ['anonymous', 'authenticated', 'administrator'])) {
        $role_list[$v->label()] = $v->label();
      }

    }
    $form['roles'] = [
      '#type' => 'select',
      '#title' => $this->t('OIDC managed Roles'),
      '#options' => $role_list,
      '#default_value' => $this->configuration['roles'],
      '#multiple' => True,
      '#description' => 'Your role name must match your m-community group name.  Using this feature will override manual assigment of selected roles.'
    ];
    $form['testshib'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use testing instance of the IDP'),
      '#description' => 'Only check this box if directed to do so by ITS',
      '#default_value' => $this->configuration['testshib']
    ];
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function getEndpoints()
  {
    if ($this->configuration['testshib'] == 1) {
      $service = json_decode(file_get_contents("https://shib-idp-staging.dsc.umich.edu/.well-known/openid-configuration"));

    } else {
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
  public function authorize($scope = '')
  {
    return parent::authorize('openid email edumember profile  account_type');
  }

  /**
   * {@inheritdoc}
   */
  public function decodeIdToken($id_token)
  {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function XretrieveUserInfo($access_token)
  {
    $request_options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $access_token,
        'Accept' => 'application/json',
      ],
    ];
    $endpoints = $this->getEndpoints();

    $client = $this->httpClient;
    try {
      $claims = [];
      $response = $client->get($endpoints['userinfo'], $request_options);
      $response_data = json_decode((string)$response->getBody(), TRUE);

      foreach ($this->userInfoMapping as $claim => $key) {
        if (array_key_exists($key, $response_data)) {
          $claims[$claim] = $response_data[$key];
        }
      }

      // X names can be empty. Fall back to the login name.
      if (empty($claims['name']) && isset($response_data['login'])) {
        $claims['name'] = $response_data['login'];
      }

      // Convert the updated_at date to a timestamp.
      if (!empty($response_data['updated_at'])) {
        $claims['updated_at'] = strtotime($response_data['updated_at']);
      }

      // The email address is only provided in the User resource if the user has
      // chosen to display it publicly. So we need to make another request to
      // find out the user's email address(es).
      if (empty($claims['email'])) {
        $email_response = $client->get($endpoints['userinfo'] . '/emails', $request_options);
        $email_response_data = json_decode((string)$email_response->getBody(), TRUE);

        foreach ($email_response_data as $email) {
          if (!empty($email['primary'])) {
            $claims['email'] = $email['email'];
            $claims['email_verified'] = $email['verified'];
            break;
          }
        }
      }

      return $claims;
    } catch (\Exception $e) {
      $variables = [
        '@message' => 'Could not retrieve user profile information',
        '@error_message' => $e->getMessage(),
      ];
      $this->loggerFactory->get('openid_connect_' . $this->pluginId)
        ->error('@message. Details: @error_message', $variables);
      return FALSE;
    }
  }

}
