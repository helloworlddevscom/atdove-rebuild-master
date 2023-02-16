<?php

namespace Drupal\atdove_billing\Controller;

use Drupal\atdove_billing\PaymentConfig;
use Drupal\atdove_billing\Services\SubscriptionService;
use Drupal\atdove_users\UsersManager;
use Drupal\atdove_utilities\ValueFetcher;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Stripe\BillingPortal\Session;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class StripeCustomerPortalSession extends ControllerBase
{
  /** @var PrivateTempStoreFactory  */
  private PrivateTempStoreFactory $tempStoreFactory;

  /** @var PrivateTempStore  */
  private PrivateTempStore $store;

  /** @var SubscriptionService  */
  private SubscriptionService $subscriptionService;

  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    SubscriptionService $subscriptionService
  )
  {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->store = $this->tempStoreFactory->get('atdove_billing_existing_org_subscription_form_data');
    $this->subscriptionService = $subscriptionService;
    PaymentConfig::setApiKey();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('tempstore.private'),
      $container->get('atdove_billing.subscription_service')
    );
  }

  /**
   * Forwards users to either Stripe to manage their account, or
   * forwards users to form to get group setup with stripe.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *lando drush upwd adminuser TestPass!
   * @return array
   * @throws \Stripe\Exception\ApiErrorException
   */
  public function forwardUserToStripe(GroupInterface $group)
  {
    $stripe_id = ValueFetcher::getFirstValue($group, 'field_stripe_customer_id');

    $subscriptionStatus = null;
    if(!is_null($stripe_id)) {
      $subscriptionStatus = $this->subscriptionService->getSubscriptionsByCustomerId($stripe_id)['status'];
    }

    // A subscription that isn't paid gets a 'canceled' status in Stripe after all collection attempts fail.  Canceled
    // subscriptions aren't passed with the customer object via the API, so retrieving a canceled subscription through the
    // customer appears as if there isn't a subscription record.  For that reason, we check for both 'canceled' and null here.
    if (!$stripe_id || is_null($subscriptionStatus) || $subscriptionStatus === 'canceled' || $group->field_license_status->value === 'inactive') {
      // This is either a legacy organization that hasn't renewed via Stripe or an organization with a canceled subscription
      // due to none payment.  Redirect them to the renewal form.
      $expiration = $group->get('field_current_expiration_date')->getValue()[0]['value'];

      $this->store->set('group', $group);
      $this->store->set('expiration', $expiration);
      $this->store->set('stripe_id', $stripe_id);

      $url = Url::fromRoute('atdove_billing.existing_org_subscribe_form', ['group' => $group->id()])->toString();

      $response = new RedirectResponse($url);
      $response->send();
    }

    // The organization has a stripe customer record AND their subscription doesn't have a status of 'canceled'.
    // Redirect them to the stripe customer portal.
    $session = Session::create([
      'customer' => $stripe_id
    ]);
    header("Location: " . $session->url);
    exit();
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    $group = \Drupal::routeMatch()->getParameter('group');

    if(is_string($group)) {
      $group = \Drupal\group\Entity\Group::load($group);
    }

    // If we don't have a valid group, EXIT!
    if (!$group instanceof Group) {
      return AccessResult::forbidden('Failed to load group when access checking.');
    }

    // All admins and billing admins can alter any groups stripe.
    if (
      UsersManager::userHasPrivilegedRole($account)
    ){
       return AccessResult::allowed();
    }

    // All Organization admin group members can edit a group.
    $member = $group->getMember($account);
    if ($member && in_array('organization-admin', array_keys($member->getRoles()))) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden('User did not pass any requirements for access allowd to stripe customer portal.');
  }
}
