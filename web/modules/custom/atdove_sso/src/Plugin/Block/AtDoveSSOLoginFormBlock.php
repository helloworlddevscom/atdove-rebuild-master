<?php

namespace Drupal\atdove_sso\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Form;
use Drupal\Core\Url;

/**
 * Provides a custom login block
 * for AtDove that displays both
 * default login block and SSO login block.
 *
 * @Block(
 *   id = "atdove_sso_login",
 *   admin_label = @Translation("AtDove SSO Login"),
 *   category = @Translation("atdove")
 * )
 */
class AtDoveSSOLoginFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $login_form = \Drupal::formBuilder()->getForm('Drupal\user\Form\UserLoginForm');
    $openid_form = \Drupal::formBuilder()->getForm('Drupal\openid_connect\Form\OpenIDConnectLoginForm');
    $join_url = Url::fromRoute('atdove_organizations_create.select_license', [], ['absolute' => TRUE]);
    $join_path = $join_url->toString();

    return [
      '#user_login_form' => $login_form,
      '#openid_form' => $openid_form,
      '#join_path' => $join_path
    ];
  }
}
