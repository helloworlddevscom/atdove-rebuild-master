<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing\Contracts;

use Stripe\Subscription;

interface SubscriptionResourceInterface
{
  /**
   * @param array $subscriptionData
   * @return Subscription
   */
  public function createSubscription(array $subscriptionData): Subscription;
}
