<?php

namespace Drupal\atdove_billing\Controller;

use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\Core\Controller\ControllerBase;
use Stripe\Event;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class StripeApiWebhook.
 *
 * Provides the route functionality for stripe_api.webhook route.
 *
 * STRIPE SUBSCRIPTION EVENT DOCUMENTATION:
 * https://stripe.com/docs/api/subscriptions/object
 */
class StripeApiWebhook extends ControllerBase {

  // Fake ID from Stripe we can check against.
  const FAKE_EVENT_ID = 'evt_00000000000000';

  // Endpoint secret value.
  private $LOCAL_ENDPOINT_SECRET;

  /**
   * @var string $env
   *
   * The environment we're dealing with.
   */
  protected $env;

  /**
   * @var string
   *
   * The security key for webhooks. Defaults to local/lando value.
   */
  private $webhook_security_key = 'whsec_ba40ded391deeb65a728b2c99dda9e513bbdc8f8e9aa227e2be4c6dac5ed9da2';

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    // Determine the environment we're in.
    if(isset($_ENV['PANTHEON_ENVIRONMENT'])) {
      switch ($_ENV['PANTHEON_ENVIRONMENT']) {
        case "live":
          $this->env = "live";
          $this->webhook_security_key = \Drupal::config('stripe.settings')->get('apikey.live.webhook');
          break;
        default:
          $this->env = "lando";
          break;
      }
    } else {
      $this->env = "testing";
    }
  }

  /**
   * Captures the incoming webhook request.
   *
   * @param $request Request
   *
   * @return Response
   */
  public function handleIncomingWebhook(Request $request) {
    $input = $request->getContent();
    $decoded_input = json_decode($input);
    $headers = $request->headers;

    // Only verify webhook if we aren't in a lano envrionemnt getting a behat request.
    if (
      $this->env === 'lando'
      && $headers->has('Local-Behat')
    ) {
      // Check we have a behat request and a correct key.
      if (
        $headers->has('Behat-Key')
        && $headers->get('Behat-Key') === 'aSb1eo6YFa86Lbvv'
      ) {
        $result = TRUE;
      }
      else {
        return new Response('INVALID AUTHENTICATION', Response::HTTP_UNAUTHORIZED);
      }
    }
    else {
      // This is the standard check that validates a Stripe Webhook.
      $result = $this->verifyWebhook(
        $request->getContent(),
        $headers->get('Stripe-Signature'),
        $this->webhook_security_key
      );
    }

    if ($result === TRUE) {
      $customer_id = $decoded_input->data->object->customer;

      switch ($decoded_input->type) {
        // Handle a subscription being deleted in the UI. "Cancel" in UI triggers this.
        case "customer.subscription.deleted":
          $this->changeGroupStatusWithCustomerId($customer_id, 'inactive');
          break;

        // Handle a subscription being created on a user who was canceled.
        case "customer.subscription.created":
          $this->changeGroupStatusWithCustomerId($customer_id, 'active');
          $this->setGroupMaxMembers($customer_id, $decoded_input->data);
          break;

        // "Organic" cancellations (non-payment, etc) are inside an updated event.
        case "customer.subscription.updated":
          switch ($decoded_input->data->object->status) {
            case "incomplete_expired":
            case "canceled":
            case "unpaid":
              $this->changeGroupStatusWithCustomerId($customer_id, 'inactive');
              // @todo Determine if we need to set to zero for unpaid or not?
              // $this->setGroupMaxMembers($customer_id, 0);
              break;
            case "active":
            case "trialing":
              $this->changeGroupStatusWithCustomerId($customer_id, 'active');
              $this->setGroupMaxMembers($customer_id, $decoded_input->data);
              break;
          }
          break;
      }
    }

    return new Response('SUCCESS.', Response::HTTP_ACCEPTED);
  }

  /**
   * Verify Stripe Webhook Signature for the event passed.
   *
   * @param $payload
   * @param $headerSig
   * @param $webhookSec
   *
   * @return array|bool
   */
  private function verifyWebhook($payload, $headerSig, $webhookSec) {
    try {
      Webhook::constructEvent(
        $payload,
        $headerSig,
        $webhookSec
      );

    }
    catch (UnexpectedValueException $e) {
      \Drupal::logger('atdove_billing')
        ->error('UnexpectedValueException in StripeApiWebhook::verifyWebhook. Error: ' . $e->getMessage());
      return FALSE;
    }
    catch (SignatureVerificationException $e) {
      \Drupal::logger('atdove_billing')
        ->error('SignatureVerificationException in StripeApiWebhook::verifyWebhook. Error: ' . $e->getMessage());
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Webhookredirect.
   *
   * @return string
   *   Redirect the user to home page and show the message.
   */
  public function webhookRedirect() {
    $this->messenger()->addMessage($this->t('The webhook route works properly.'));
    return new RedirectResponse(Url::fromRoute('<front>')->setAbsolute()->toString());
  }

  /**
   * @param string $customer_id
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   Load the group for customer ID, or false if no group.
   */
  private function fetchGroupForCustomerId(string $customer_id) {
    $groups = \Drupal::entityTypeManager()
      ->getStorage('group')
      ->loadByProperties([
        'type' => 'organization',
        'field_stripe_customer_id' => $customer_id
      ]);

    if (!empty($groups)) {
      return reset($groups);
    }
    else {
      \Drupal::logger('atdove_billing')->error(
        "Failed to load group(s) with customer_id: $customer_id"
      );
      return FALSE;
    }
  }

  /**
   * Helper method for setting the max members a particular group can have.
   *
   * Searches description for intval() then title, then sets default and
   * logs warning.
   *
   * @param string $customer_id
   *  The Stripe customer ID to search for a group for.
   * @param object $data
   *  The data returned by the query.
   */
  private function setGroupMaxMembers(string $customer_id, object $data) {
    $group = $this->fetchGroupForCustomerId($customer_id);

    if ($group) {
      $group_membership_limit = OrganizationsManager::discernMemberLimit(
        $customer_id,
        $data->object->metadata->license_tier,
        $data->object->plan->nickname
      );

      $group->set('field_member_limit', intval($group_membership_limit));
      $result = $group->save();

      if($result !== SAVED_UPDATED || $group->get('field_member_limit')->value !== $group_membership_limit ) {
        \Drupal::logger('atdove_billing')->error('StripeApiWebhook::setGroupMaxMembers member limit (@member_limit) update failed with result: @result, for stripe ID: @stripe_id',
          [
            '@member_limit' => $group_membership_limit,
            '@result' => $result,
            '@stripe_id' => $customer_id
          ]
        );
      }
    }
  }

  /**
   * Helper method for changing a groups status for a given customer id.
   *
   * @param string $customer_id
   *   The Stripe customer ID to search for a group for.
   * @param string $status
   *   The Status to set field_license_status
   */
  private function changeGroupStatusWithCustomerId(string $customer_id, string $status) {
    $acceptable_statuses = ['active', 'inactive'];

    $group = $this->fetchGroupForCustomerId($customer_id);

    if ($group) {
      if (!in_array($status, $acceptable_statuses)) {
        // @todo: convert logger approach to DI methodology.
        \Drupal::logger('atdove_stripe')->error(
          "Invalid status passed to StripeApiWebhook::changeGroupStatusWithCustomerId. VALUE" . $status
        );
        return;
      }
      $group->set('field_license_status', $status);
      $group->save();
    }
  }

}
