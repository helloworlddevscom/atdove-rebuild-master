<?php
/**
 * @file
 * Contains \Drupal\atdove_organizations_create\Form\CreateAcctOrg\CreateAcctOrgFormStepThree.
 */

namespace Drupal\atdove_organizations_create\Form\CreateAcctOrg;

use Drupal\atdove_billing\BillingConstants;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\atdove_billing\PaymentConfig;


/**
 * Class CreateAcctOrgFormStepThree
 * @package Drupal\atdove_organizations_create\Form\CreateAcctOrg
 *
 * Step three of multi-step form for anonymous user to create an account, organization,
 * and send billing info to Stripe.
 */
class CreateAcctOrgFormStepThree extends CreateAcctOrgFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'atdove_organizations_create_acct_org_form_three';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $form = parent::buildForm($form, $form_state);

    $license_id = $this->store->get('license_id');
    $license_name = $this->store->get('license_name');
    $license_price = $this->store->get('license_price');
    $license_interval = $this->store->get('license_interval') === "year" ? "Annual" : "Monthly";
    $total = $license_price;

    // @TODO: Refactor this into a template.

    // Stripe Payment UI
    $form['#prefix'] = '<div class="payment-ui payment-ui-card">';

    // Display summary of license.
    $form['#prefix'] .= '<div class="payment-ui-container fieldgroup">';
    $form['#prefix'] .= '<div class="payment-ui-container-header">';
    $form['#prefix'] .= '<p>' . t('Checkout') . '</p>';
    $form['#prefix'] .= '</div>';
    $form['#prefix'] .= '<div class="form__billing-notice">';
    $form['#prefix'] .= '<p>' . t('Your first 7 days are free! Your credit card will be charged when your free trial ends.') . '</p>';
    $form['#prefix'] .= '<div class="content">';
    $form['#prefix'] .= '<div id="payment-form">';
    $form['#prefix'] .= '<!-- Stripe Elements Placeholder -->';
    $form['#prefix'] .= '<div id="card-element" class="form-control"></div>';
    $form['#prefix'] .= '<!-- Used to display Element errors. -->';
    $form['#prefix'] .= '<div id="card-errors" role="alert"></div>';
    $form['#prefix'] .= '</div>';
    $form['#prefix'] .= '</div>';
    $form['#prefix'] .= '</div>';
    $form['#prefix'] .= '<div class="form__license-info payment-ui-card">';
    $form['#prefix'] .= '<p class="license-info__title"><h4>' . t('Future Billing') . '</h4></p>';
    $form['#prefix'] .= '<div class="license-info__product">';
    $form['#prefix'] .= "<p class='product__title'><h2>$license_name</h2></p>";
    $form['#prefix'] .= '</div>';
    $form['#prefix'] .= '<div class="license-info__price">';
    $form['#prefix'] .= '<span class="price__header">' . t($license_interval . ' Subscription Fee') . ': </span>';
    $form['#prefix'] .= "<span class='price__title'>$$license_price</span>";
    $form['#prefix'] .= '</div></div></div></div>';

    $form['actions']['previous'] = array(
      '#type' => 'link',
      '#title' => $this->t('Previous'),
      '#attributes' => array(
        'class' => array('button'),
      ),
      '#weight' => 0,
      '#url' => Url::fromRoute('atdove_organizations_create.create_acct_org_form_two'),
    );

    $form['#attached']['library'][] = 'atdove_billing/stripejs';
    $form['#attached']['library'][] = 'atdove_billing/billingjs';
    $form['#attached']['drupalSettings']['billing']['stripe'] = [
      'pubkey' => PaymentConfig::getPubKey()
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @TODO: Validate billing info.
    // Should we validate that we can establish a connection to Stripe?
    // Does Stripe have some kind of validation methods?
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    try {
      if(!isset($form_state->getUserInput()['token'])) {
        throw new \Exception('secure payment token not submitted');
      }
    } catch(\Exception $exception) {
      \Drupal::logger(self::MODULE)->error($exception);
      \Drupal::messenger()->addError(t(BillingConstants::SUBSCRIPTION_ERROR));
      $form_state->setRedirect($this->currentRouteName);
      return;
    }

    $this->store->set('token', $form_state->getUserInput()['token']);

    // Save the data.
    // Returns true if the subscription is created successfully, and if the user registration and login is successful.
    $result = parent::saveData($form_state);

    if($result) {
      $form_state->setRedirect('<front>');
      //
      //    $response =  new RedirectResponse(\Drupal::url('/user', [], ['absolute' => TRUE]));
      ////    $response = new RedirectResponse($user_destination);
      //    $response->send();
      \Drupal::messenger()->addMessage(t('Welcome to AtDove!'));
    }
  }
}
