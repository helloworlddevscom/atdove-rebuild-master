<?php

  namespace Drupal\atdove_sso\Plugin\OpenIDConnectClient;

  use Drupal\Core\Form\FormStateInterface;
  use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;
  use Drupal\Core\Language\LanguageInterface;
  use Drupal\Core\Url;

  /**
   * OpenAthens BluePearl OpenID Connect client.
   *
   * Used to connect via OpenAthens, specifically for BluePearl Pet Hospital users.
   *
   * @OpenIDConnectClient(
   *   id = "openathens_bluepearl",
   *   label = @Translation("OpenAthens BluePearl")
   * )
   */
  class OpenIDConnectOpenAthensBluePearlClient extends OpenIDConnectClientBase {

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $configuration) {
      $this->configuration = [
          'client_id' => \Drupal::config('atdovesso.settings')->get('client_id'),
          'client_secret' => \Drupal::config('atdovesso.settings')->get('client_secret'),
          'authorization_endpoint' => \Drupal::config('atdovesso.settings')->get('authorization_endpoint'),
          'token_endpoint' => \Drupal::config('atdovesso.settings')->get('token_endpoint'),
          'userinfo_endpoint' => \Drupal::config('atdovesso.settings')->get('userinfo_endpoint'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
      $form = parent::buildConfigurationForm($form, $form_state);

      $form['authorization_endpoint'] = [
        '#title' => $this->t('Authorization endpoint'),
        '#type' => 'textfield',
        '#default_value' => $this->configuration['authorization_endpoint'],
      ];
      $form['token_endpoint'] = [
        '#title' => $this->t('Token endpoint'),
        '#type' => 'textfield',
        '#default_value' => $this->configuration['token_endpoint'],
      ];
      $form['userinfo_endpoint'] = [
        '#title' => $this->t('UserInfo endpoint'),
        '#type' => 'textfield',
        '#default_value' => $this->configuration['userinfo_endpoint'],
      ];

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoints() {
      return [
        'authorization' => $this->configuration['authorization_endpoint'],
        'token' => $this->configuration['token_endpoint'],
        'userinfo' => $this->configuration['userinfo_endpoint'],
      ];
    }

   /**
     * {@inheritdoc}
     */
  protected function getRedirectUrl(array $route_parameters = [], array $options = []) {
    $language_none = $this->languageManager
      ->getLanguage(LanguageInterface::LANGCODE_NOT_APPLICABLE);

    $route_parameters += [
      'client_name' => "openathens_bluepearl",
    ];
    $options += [
      'absolute' => TRUE,
      'language' => $language_none,
    ];
    return Url::fromRoute('atdove_sso.redirect_controller_redirect', $route_parameters, $options);
  }

}
