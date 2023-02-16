<?php
/**
 * @file
 * Contains \Drupal\atdove_organizations_create\Form\CreateAcctOrg\CreateAcctOrgFormStepTwo.
 */

namespace Drupal\atdove_organizations_create\Form\CreateAcctOrg;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class CreateAcctOrgFormStepTwo
 * @package Drupal\atdove_organizations_create\Form\CreateAcctOrg
 *
 * Step two of multi-step form for anonymous user to create an account, organization,
 * and send billing info to Stripe.
 */
class CreateAcctOrgFormStepTwo extends CreateAcctOrgFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'atdove_organizations_create_acct_org_form_two';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['org_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Organization Name'),
      '#default_value' => $this->store->get('org_name') ? $this->store->get('org_name') : '',
      '#required' => true
    );

    $form['actions']['previous'] = array(
      '#type' => 'link',
      '#title' => $this->t('Previous'),
      '#attributes' => array(
        'class' => array('button'),
      ),
      '#weight' => 0,
      '#url' => Url::fromRoute('atdove_organizations_create.create_acct_org_form_one'),
    );

    $form['actions']['submit']['#value'] = $this->t('Next');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('org_name', $form_state->getValue('org_name'));

    $form_state->setRedirect('atdove_organizations_create.create_acct_org_form_three');
  }
}
