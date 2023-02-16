<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing\Services;

use Carbon\Carbon;
use Drupal\atdove_billing\PaymentConfig;
use Drupal\atdove_billing\Resources\SubscriptionResource;
use Stripe\Collection;
use Stripe\Customer;
use Stripe\Subscription;
use Drupal\Core\TempStore\PrivateTempStore;

class SubscriptionService
{
  /** @var SubscriptionResource */
  private SubscriptionResource $subscriptionResource;

  /** @var PrivateTempStore  */
  private PrivateTempStore $store;

  public function __construct(
    SubscriptionResource $subscriptionResource
  )
  {
    $this->subscriptionResource = $subscriptionResource;
    PaymentConfig::setApiKey();
  }

  /**
   * @param string $license
   * @param Customer $customer
   * @return Subscription
   * @throws \Stripe\Exception\ApiErrorException
   */
  public function createSubscription(string $license, string $tier, Customer $customer, $expiration = null): Subscription
  {
    // Now create the subscription
    $subscriptionData = [
      'customer' => $customer->id,
      'items' => [
        ['price' => $license],
      ]
    ];

    // Non-legacy accounts don't have an expiration date stored locally, and they receive a 7-day free-trial.
    if(is_null($expiration)) {
      $subscriptionData['trial_end'] = $this->getTrialEndTimestamp();
    }

    // If the legacy subscription is still active, then we'll effectively begin the new subscription on the legacy expiration date
    // by setting a trial period on the subscription that ends on the legacy expiration date.
    // See: https://stripe.com/docs/billing/subscriptions/trials
    if($expiration > Carbon::now()) {
      $subscriptionData['trial_end'] = $expiration->timestamp;
    }

    // Pass the license tier with the subscription request
    $subscriptionData['metadata'] = ["license_tier" => $tier];

    return $this->subscriptionResource->createSubscription($subscriptionData);
  }

  /**
   * @param string $stripe_id
   * @return array|null
   * @throws \Stripe\Exception\ApiErrorException
   */
  public function getSubscriptionsByCustomerId(string $stripe_id)
  {
    $subscriptions = $this->subscriptionResource->getSubscriptions(['customer' => $stripe_id]);

    // Returning the first item only as there should only be 1
    return $subscriptions[0];
  }

  /**
   * @return float|int|string
   */
  private function getTrialEndTimestamp(): string
  {
    return Carbon::now()->addDays(7)->timestamp;
  }

}
