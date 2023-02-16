<?php
/**
 * @file
 * Contains \Drupal\atdove_organizations_create\Form\CreateAcctOrg\CreateAcctOrgFormStepOne.
 */

namespace Drupal\atdove_organizations_create\Form\CreateAcctOrg;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Class CreateAcctOrgFormStepOne
 * @package Drupal\atdove_organizations_create\Form\CreateAcctOrg
 *
 * Step one of multi-step form for anonymous user to create an account, organization,
 * and send billing info to Stripe.
 */
class CreateAcctOrgFormStepOne extends CreateAcctOrgFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'atdove_organizations_create_acct_org_form_one';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['first_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#default_value' => $this->store->get('first_name') ? $this->store->get('first_name') : '',
      '#required' => true,
    );

    $form['last_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#default_value' => $this->store->get('last_name') ? $this->store->get('last_name') : '',
      '#required' => true,
    );

    $form['email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#default_value' => $this->store->get('email') ? $this->store->get('email') : '',
      '#required' => true,
    );

    $form['password'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => $this->store->get('password') ? $this->store->get('password') : '',
      '#required' => true,
    );

    $form['password_confirm'] = array(
      '#type' => 'password',
      '#title' => $this->t('Confirm Password'),
      '#default_value' => $this->store->get('password_confirm') ? $this->store->get('password_confirm') : '',
      '#required' => true,
    );

    $form['actions']['submit']['#value'] = $this->t('Next');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Verify email is valid.
    if (!\Drupal::service('email.validator')->isValid($form_state->getValue('email'))) {
      $form_state->setErrorByName('email', t('Please enter a valid email address.'));
    }

    // Verify email is not already in use.
    $account = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $form_state->getValue('email')]);
    if (!empty($account)) {
      $form_state->setErrorByName('email', t('An account already exists with this email address.'));
    }

    // Verify password and password_confirm match.
    if ($form_state->getValue('password') !== $form_state->getValue('password_confirm')) {
      $form_state->setErrorByName('password', t('Password and Confirm Password must match.'));
    }

    // @TODO: Are there core validations we can perform on password fields?
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('first_name', $form_state->getValue('first_name'));
    $this->store->set('last_name', $form_state->getValue('last_name'));
    $this->store->set('email', $form_state->getValue('email'));
    $this->store->set('password', $form_state->getValue('password'));
    $this->store->set('password_confirm', $form_state->getValue('password_confirm'));
    $form_state->setRedirect('atdove_organizations_create.create_acct_org_form_two');
  }
}
