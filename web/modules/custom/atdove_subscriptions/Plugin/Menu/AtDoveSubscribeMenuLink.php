<?php

namespace Drupal\atdove_subscriptions\Plugin\Menu;

use Drupal\stripe_registration\Plugin\Menu\SubscribeMenuLink;



//Look up groups user blongs to. if user is the admin for the group, look up subscription. 
// If subscription is active, display link "Manage Subscription", BUT
// If they are not already a customer, display "Update Payment Information"
// If not active, display "Upgrade" in menu
class AtDoveSubscribeMenuLink extends SubscribeMenuLink {

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\stripe_registration\Controller\UserSubscriptionsController::subscribeTitle()
   */
  public function getTitle() {
    $stripe_registration = \Drupal::service('stripe_registration.stripe_api');
    $current_user = \Drupal::service('current_user');
    if ($stripe_registration->userHasStripeSubscription($current_user)) {
      return 'Upgrade';
    }
    return 'Subscribe';
  }

}
