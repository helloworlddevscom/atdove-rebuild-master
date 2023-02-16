<?php

namespace Drupal\atdove_billing\EventSubscriber;

use Drupal\Core\Form\FormStateInterface;
use Drupal\core_event_dispatcher\Event\Form\FormAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\atdove_billing\PaymentConfig;
use Stripe\Exception\InvalidRequestException;

/**
 * Subscribe to form events (you can alter forms here) and alter them.
 *
 * SEE: hook_event_dispatcher/examples/ExampleFormEventSubscribers.php .
 */
class AtDoveBillingFormsEventSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * Constructor.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   *
   * Establish what events this subscriber responds to.
   *
   * To add specific base form forms:
   * 'hook_event_dispatcher.form_base_node_form.alter' => 'alterNodeForm',
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::FORM_ALTER => 'alterForm',
    ];
  }

  /**
   * EQUIVALENT: hook_form_alter.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormAlterEvent $event
   *   The event.
   */
  public function alterForm(FormAlterEvent $event): void {
    $form_id = $event->getFormId();
    $form = &$event->getForm();

    if (isset($form['field_stripe_customer_id'])) {
      $form['#validate'][] = get_class($this) . '::verifyStripeCustomerID';
    }
  }

  /**
   * Form Validation function.
   *
   * Verifies that the stripe ID field contains a valid stripe ID.
   */
  public static function verifyStripeCustomerID($form, FormStateInterface $form_state) {
    $stripe_id_field = 'field_stripe_customer_id';

    // Check field even has a value to avoid errors.
    if ($form_state->hasValue($stripe_id_field)) {
      $stripe_id_passed = $form_state->getValue($stripe_id_field)[0]['value'];

      // Functionality allowing testing
      if (isset($_ENV['PANTHEON_ENVIRONMENT']) && $_ENV['PANTHEON_ENVIRONMENT'] == 'lando') {
        if ($stripe_id_passed == 'BEHAT_TEST_PASS') {
          return TRUE;
        }
        if ($stripe_id_passed == 'BEHAT_TEST_FAIL') {
          $form_state->setErrorByName($stripe_id_field, "The Stripe ID ($stripe_id_passed) could not be validated by stripe. Please double check the ID and try again.");
        }
      }


      // Check if the a blank value wasn't entered. If it's blank, we do not care.
      if (strlen($stripe_id_passed) > 0) {
        // @todo IMPROVE? Not ideal to set API key first then call service.
        PaymentConfig::setApiKey();
        $customer_resource = \drupal::service('atdove_billing.customer_resource');

        $customer = $customer_resource->getCustomerByID($stripe_id_passed);

        if ($customer === FALSE) {
          $form_state->setErrorByName($stripe_id_field, "The Stripe ID ($stripe_id_passed) could not be validated by stripe. Please double check the ID and try again.");
        }
      }
    }
  }
}
