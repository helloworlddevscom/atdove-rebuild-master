<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing\Resources;

use Drupal\atdove_billing\Contracts\SubscriptionResourceInterface;
use Stripe\Collection;
use Stripe\Subscription;
use Stripe\Exception\ApiErrorException;

class SubscriptionResource implements SubscriptionResourceInterface
{

  /**
   * @param array $subscriptionData
   * @return Subscription
   * @throws ApiErrorException
   */
  public function createSubscription(array $subscriptionData): Subscription
  {
    return Subscription::create($subscriptionData);
  }

  /**
   * @param array $params
   * @return array
   * @throws ApiErrorException
   */
  public function getSubscriptions(array $params): array
  {
    $subscriptions =  Subscription::all($params);

    if(is_null($subscriptions) || is_null($subscriptions['data']) || count($subscriptions['data']) < 1) {
      return [];
    }

    return $subscriptions['data'];
  }
}
