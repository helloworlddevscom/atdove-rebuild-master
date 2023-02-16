<?php

namespace Drupal\atdove_billing\Services;

use Drupal\atdove_billing\BillingConstants;
use Stripe\Customer;

class RegistrationService
{
  /** @var SubscriptionService  */
  private $subscriptionService;

  /** @var CustomerService  */
  private $customerService;

  /** @var CardService  */
  private $cardService;

  public function __construct(
    SubscriptionService $subscriptionService,
    CustomerService $customerService,
    CardService $cardService
  )
  {
    $this->subscriptionService = $subscriptionService;
    $this->customerService = $customerService;
    $this->cardService = $cardService;
  }

  /**
   * @param $args
   * @param $formState
   * @param $route
   * @param $stripe_id
   * @return array|false
   * @throws \Stripe\Exception\ApiErrorException
   */
  public function register($args, $formState, $route, $stripe_id = null)
  {
    $renew = false;

    // To create a subscription we first need a Stripe Customer
    if(!is_null($stripe_id)) {
      $customer = Customer::retrieve($stripe_id, []);
      $renew = true;
    } else {
      try {
        $customer = $this->customerService->createCustomer(
          [
            'description' => 'Org: ' . $args['org_name'],
            'email' => $args['customer_email']
          ]
        );
      } catch(\Exception $exception) {
        \Drupal::logger(BillingConstants::MODULE)->error($exception);
        \Drupal::messenger()->addError(t(BillingConstants::SUBSCRIPTION_ERROR));
        $formState->setRedirect($route);
        return false;
      }
    }

    // Log the registration details for troubleshooting
    \Drupal::logger(BillingConstants::MODULE)->info(json_encode([
      'existing_stripe_id' => $stripe_id,
      'email' => $args['customer_email'],
      'org_name' => $args['org_name'],
      'renew' => $renew
    ]));

    // Next let's create a Stripe Card on the customer
    try {
      $this->cardService->createSource($customer->id, ['source' => $args['token']]);
    } catch(\Exception $exception) {
      \Drupal::logger(BillingConstants::MODULE)->error($exception);
      \Drupal::messenger()->addError(t(BillingConstants::SUBSCRIPTION_ERROR));
      $formState->setRedirect($route);
      return false;
    }

    // Create the subscription
    try {
      if(isset($args['expiration'])) {
        // Legacy accounts have an expiration date.  Legacy account subscriptions begin on the legacy expiration date.
        // (They begin in a trial state that ends on the legacy expiration date).
        $subscriptionData = $this->subscriptionService->createSubscription($args['license'], $args['license_name'], $customer, $args['expiration']);
      } else {
        // Non-legacy account subscriptions begin immediately (with a 7 day free trial).
        $subscriptionData = $this->subscriptionService->createSubscription($args['license'], $args['license_name'], $customer);
      }
    } catch(\Exception $exception) {
      \Drupal::logger(BillingConstants::MODULE)->error($exception);
      \Drupal::messenger()->addError(t(BillingConstants::SUBSCRIPTION_ERROR));
      $formState->setRedirect($route);
      return false;
    }

    return [
      'subscription' => $subscriptionData,
      'customer' => $customer
      ];
  }
}
