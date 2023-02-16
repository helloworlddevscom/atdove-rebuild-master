<?php

namespace Drupal\atdove_billing\Form;

use Carbon\Carbon;
use Drupal\atdove_billing\BillingConstants;
use Drupal\atdove_billing\PaymentConfig;
use Drupal\atdove_billing\Services\PricingService;
use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\atdove_billing\Services\RegistrationService;

class ExistingOrgSubscribe extends FormBase
{

  /** @var PricingService */
  private PricingService $pricingService;

  /** @var RegistrationService  */
  private RegistrationService $registrationService;

  /** @var string|null */
  private ?string $currentRouteName;

  /** @var string  */
  private string $description;

  /** @var PrivateTempStoreFactory  */
  private PrivateTempStoreFactory $tempStoreFactory;

  /** @var PrivateTempStore  */
  private PrivateTempStore $store;

  /** @var mixed  */
  private $org;

  /** @var Carbon  */
  private $expirationDate;

  /** @var array  */
  private array $pricingOptions;

  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    PricingService      $pricingService,
    RegistrationService $registrationService
  )
  {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->pricingService = $pricingService;
    $this->registrationService = $registrationService;

    $this->store = $this->tempStoreFactory->get('atdove_billing_existing_org_subscription_form_data');

    $this->currentRouteName = \Drupal::routeMatch()->getRouteName();

    // Prepare description (expiration/signup info)
    $expiration = $this->store->get('expiration');
    $this->expirationDate = Carbon::parse($expiration);

    if (!is_null($this->store->get('stripe_id'))) {
      $message = $this->t('Oops, looks like your subscription has either been canceled or expired.');
      $this->description = "<p>$message</p>";
      $this->description .= "<p>Please use the form below to renew your license and enable auto-collection.</p>";
    } else if ($expiration !== "1970-01-01T00:00:00" && $this->expirationDate < Carbon::now()) {
      $this->description = sprintf(
        "<p>The license for this account was created via our legacy system and expired on <strong>%s</strong>.</p>",
        $this->expirationDate->format('M d Y')
      );
      $this->description .= "<p>To renew the license and enable auto-collection via our new system, please use the form below.</p>";
    } else {
      $this->description = sprintf(
        "<p>The license for this account was created via our legacy system and expires on <strong>%s</strong>.</p>",
        $this->expirationDate->format('M d Y')
      );
      $this->description .= "<p>Please subscribe via our new system prior to the expiration date using the form below to enable auto collection.<br>";
      $this->description .= sprintf("Collection via our new system won't begin until <strong>%s</strong>.</p>", $this->expirationDate->format('M d Y'));
    }

    $this->description .= "<br><p>If you would like to decrease your subscription level, please <a target=\"_blank\" href='https://knowledge.atdove.org/knowledge/kb-tickets/new'>contact support</a>.</p>";

    $this->org = $this->store->get('group');
    $org_members = \Drupal::service('group.membership_loader')->loadByGroup($this->org);
    $memberCount = count($org_members);

    $pricing = $this->pricingService->getPricing($memberCount);

    $this->pricingOptions['select'] = ' ---- Select a plan ---- ';
    foreach ($pricing as $price) {
      $this->pricingOptions[$price['id']] = sprintf("%s, %s per %s", $price['seats'], $price['amount'], $price['interval']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('tempstore.private'),
      $container->get('atdove_billing.pricing_service'),
      $container->get('atdove_billing.registration_service')
    );
  }

  /**
   * @return string
   */
  public function getFormId()
  {
    return 'atdove_billing_existing_org_subscribe_form';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) : array {

    if (
      strtotime($this->expirationDate->toString()) > strtotime('now + 2 years')
      && $this->org->field_stripe_customer_id->isEmpty()
    ) {
      $this->messenger()->addError(
        $this->t('Billing for this Organization was done in our legacy system and requires an administrator to make changes. Please contact a site administrator for further help.')
      );
      return ['#markup' => $this->t('Manage Billing Unavailable At This Time.')];
    }

    $form['options'] = array(
      '#title' => t('Pick Your Plan'),
      '#type' => 'select',
      '#options' => $this->pricingOptions,
      '#default_value' => 'select'
    );

    $form['#prefix'] = '<div class="description">' . $this->description . '</div>';
    $form['#prefix'] .= '<div class="payment-ui payment-ui-card">';
    $form['#prefix'] .= '<div class="payment-ui-container fieldgroup">';
    $form['#prefix'] .= '<div class="payment-ui-container-header">';
    $form['#prefix'] .= '<p>' . t('Checkout') . '</p>';
    $form['#prefix'] .= '</div>';

    $form['description']['#prefix'] = '<div class="form__billing-notice">';
    $form['description']['#prefix'] .= '<div class="content">';
    $form['description']['#prefix'] .= '<div id="payment-form">';

    $form['description']['#prefix'] .= '<!-- Stripe Elements Placeholder -->';
    $form['description']['#prefix'] .= '<div id="card-element" class="form-control"></div>';
    $form['description']['#prefix'] .= '<!-- Used to display Element errors. -->';
    $form['description']['#prefix'] .= '<div id="card-errors" role="alert"></div>';
    $form['#suffix'] = '</div></div></div></div></div>';

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#weight' => 10,
    );

    $form['#attached']['library'][] = 'atdove_billing/stripejs';
    $form['#attached']['library'][] = 'atdove_billing/billingjs';
    $form['#attached']['drupalSettings']['billing']['stripe'] = [
      'pubkey' => PaymentConfig::getPubKey()
    ];

    return $form;
  }

  /**
   * Implements form validation.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $formState
   */
  public function validateForm(array &$form, FormStateInterface $formState)
  {
    $option = $formState->getValue('options');
    if ($option === "select") {
      $formState->setErrorByName('option', $this->t('Please select a plan.'));
      return;
    }

    // Check expiration date isn't too far in the future for stripe (5 years).
    if (strtotime($this->expirationDate->toString()) > strtotime('now + 2 years')) {
      $formState->setErrorByName('option', $this->t('There was an error submitting the form, please contact a site administrator for help. Error: Expiration date greater than 2 years.'));
      return;
    }
  }

  /**
   * Implements a form submit handler.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $formState
   */
  public function submitForm(array &$form, FormStateInterface $formState)
  {
    try {
      if (!isset($formState->getUserInput()['token'])) {
        throw new \Exception('secure payment token not submitted');
      }
    } catch (\Exception $exception) {
      \Drupal::logger(BillingConstants::MODULE)->error($exception);
      \Drupal::messenger()->addError(t(BillingConstants::SUBSCRIPTION_ERROR));
      $formState->setRedirect($this->currentRouteName);
      return;
    }

    // Create subscription
    $subscriptionData = $this->registrationService->register(
      [
        "org_name" => $this->org->label->getValue()[0]['value'],
        'customer_email' => \Drupal::currentUser()->getEmail(),
        "token" => $formState->getUserInput()['token'],
        "license" => $formState->getValue('options'),
        "license_name" => trim(explode(",", $this->pricingOptions[$formState->getValue('options')])[0]),
        "expiration" => $this->expirationDate
      ],
      $formState,
      $this->currentRouteName,
      $this->store->get('stripe_id')
    );

    // Sanity check we even got an object back from stripe.
    if (!is_object($subscriptionData['customer'])) {
      $this->messenger()->addError($this->t('There was an error submitting the form, please contact a site administrator for help. Error: Invalid stripe customer response.'));
      return;
    }

    if (!is_object($subscriptionData['subscription'])) {
      $this->messenger()->addError($this->t('There was an error submitting the form, please contact a site administrator for help. Error: Invalid stripe subscription response.'));
      return;
    }

    // Set the Stripe ID on the group
    $this->org->set('field_stripe_customer_id', $subscriptionData['customer']->id);

    // Set the group license status
    $this->org->set('field_license_status', 'active');

    // Set the group member limit
    $group_membership_limit = OrganizationsManager::discernMemberLimit(
      $subscriptionData['customer']->id,
      $subscriptionData['subscription']->metadata->license_tier,
      $subscriptionData['subscription']->plan->nickname
    );

    $this->org->set('field_member_limit', $group_membership_limit);

    $result = $this->org->save();

    if($result !== SAVED_UPDATED || $this->org->get('field_member_limit')->value !== $group_membership_limit ) {
      \Drupal::logger('atdove_billing')->error('ExistingOrgSubscribe:submitForm member limit (@member_limit) update failed with result: @result, for stripe ID: @stripe_id',
        [
          '@member_limit' => $group_membership_limit,
          '@result' => $result,
          '@stripe_id' => $subscriptionData['customer']->id
        ]
      );
    }

    $formState->setRedirect('<front>');

    \Drupal::messenger()->addMessage(t('Thank you, your account has been updated via our new system.'));
  }
}
