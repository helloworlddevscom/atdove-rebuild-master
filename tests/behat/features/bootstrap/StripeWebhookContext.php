<?php
namespace Drupal\Tests\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

/**
 * Defines Stripe Webhook API application features from the specific context.
 *
 * NOTE: Very specific to the AtDove rebuild endpoint. Likely not portable.
 */
class StripeWebhookContext extends RawDrupalContext {

  // Mapping for event types and JSON files corresponding to them.
  private $jsonMapping = [
    "subscription deleted" => "subscription.deleted.json",
    "subscription created" => "subscription.created.json",
    "subscription expired" => "subscription.expired.json",
    "subscription renewed" => "subscription.renewed.json",
    "subscription trialing" => "subscription.trialing.json",
    "subscription unpaid" => "subscription.unpaid.json",
    "subscription created no description" => "subscription.created.noDescription.json",
    "subscription created no anything" => "subscription.created.noLimitValues.json",
  ];

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

  /**
   * Checks against Drupal if the module is enabled.
   *
   * Scenario: Checking that 'Module plain text name' is enabled at
   *   "admin/modules" Then the module "machine_name" should be enabled
   *
   * @When I trigger the webhook for a :event event with stripe customerid of :customer_id
   *
   * @param string $event
   *   The event you want to emulate. Options are:
   *   "subscription deleted" -- triggered when a subscription manually canceled in Stripe GUI.
   *   "subscription created" -- triggered when a subscription manually created in Stripe GUI.
   *   "subscription expired" -- Triggered when a subscription updates and status moves to canceled.
   *   "subscription renewed" -- Triggered when a subscription updates and status moves to active.
   *   "subscription created no description" -- Create subscription event, but no description field to discern member limit.
   *   "subscription created no anything" -- Create subscription event, but no fields to discern member limit.
   */
  public function webhookTriggerEvent($event, $customer_id)
  : void {

    $request_body = '';

    if (array_key_exists($event, $this->jsonMapping)) {
      // Depending on whether the test runs via Lando
      // or CircleCI, the path will differ.
      $path = 'tests/behat/features/bootstrap/TestContent/json/';
      if (file_exists('/app/' . $path . $this->jsonMapping[$event])) {
        $file_content = file_get_contents('/app/' . $path . $this->jsonMapping[$event]);
      }
      else {
        $file_content = file_get_contents('/home/circleci/project/' . $path . $this->jsonMapping[$event]);
      }

      $request_body = json_decode($file_content);
      $request_body->data->object->customer = $customer_id;
    }
    else {
      throw new \RuntimeException("Unexpected event type: '{$event}'.");
    }

    $client = \Drupal::httpClient();

    $behat_key = 'aSb1eo6YFa86Lbvv';
    $path = '/dove-stripe/webhook';

    try {
      $response = $client->request(
        'POST',
        'https://atdove-rebuild.lando' . $path,
        [
          'headers' => [
            'Content-Type' => 'application/json',
            'Local-Behat' => TRUE,
            'Behat-Key' => $behat_key,
          ],
          'body'=> json_encode($request_body),
        ],
      );

      if ($response->getStatusCode() !== 202) {
        throw new \RuntimeException("Unexpected response code from webhook endpoint: '{$response->getStatusCode()}'.");
      }
    }
    catch (\Exception $e) {
      // If that didn't work, maybe we're on CircleCI.
      // Try URL configured in .ci/test/behat/env/drupal-circleci-behat.conf.
      try {
        $response = $client->request(
          'POST',
          'http://drupal-circleci-behat.localhost' . $path,
          [
            'headers' => [
              'Content-Type' => 'application/json',
              'Local-Behat' => TRUE,
              'Behat-Key' => $behat_key,
            ],
            'body'=> json_encode($request_body),
          ],
        );

        if ($response->getStatusCode() !== 202) {
          throw new \RuntimeException("Unexpected response code from webhook endpoint: '{$response->getStatusCode()}'.");
        }
      }
      catch (\Exception $e) {
        throw new \RuntimeException("Unexpected exception making request: '{$e->getMessage()}'.");
      }
    }
  }

}
