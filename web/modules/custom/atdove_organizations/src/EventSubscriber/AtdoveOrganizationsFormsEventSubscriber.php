<?php

namespace Drupal\atdove_organizations\EventSubscriber;

use Drupal\atdove_billing\PaymentConfig;
use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\atdove_users\UsersManager;
use Drupal\atdove_utilities\ValueFetcher;
use Drupal\core_event_dispatcher\Event\Form\FormAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Subscribe to form events (you can alter forms here) and alter them.
 *
 * SEE: hook_event_dispatcher/examples/ExampleFormEventSubscribers.php .
 */
class AtdoveOrganizationsFormsEventSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

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
    $form_state = $event->getFormState();

    switch ($form_id){
      case "group_organization_edit_form":
        // Disable access to some fields.
        $this->restrictAccessToStripeField($form);

        // Hide license status except for admins.
        if (!UsersManager::userHasPrivilegedRole(\Drupal::currentUser())) {
          $form['field_member_limit']['#access'] = FALSE;
          $form['field_license_status']['#access'] = FALSE;
        }

        // Hide revision info.
        $form['revision_information']['#access'] = FALSE;

        break;

      case "group_content_organization-group_membership_delete_form":
        $this->disableIfOnlyOneOrgAdmin($form);
        $this->disableIfOrgMemberIsStripeEmail($form);
        break;

      case "user_cancel_form":
        $this->disableIfAnyOrgAdminAndStripeEmail($form);
        break;
    }
  }

  /**
   * Restrict access to the stripe field on organizations.
   */
  private function restrictAccessToStripeField(&$form) {
    if (!UsersManager::userHasPrivilegedRole(\Drupal::currentUser())) {
      $form['field_stripe_customer_id']['#access'] = FALSE;
    }
    else {
      $form['field_stripe_customer_id']['#attached']['library'][] = 'atdove_organizations/alert_stripe_field';
    }
  }

  /**
   * Disable form if there is only one org admin for the group
   */
  private function disableIfOnlyOneOrgAdmin(&$form) {
    $parameters = \Drupal::routeMatch()->getParameters();
    $group = $parameters->get('group');
    $user = $parameters->get('group_content')->getEntity();

    if (
      OrganizationsManager::isUserOrgAdmin($user, $group)
      && OrganizationsManager::orgAdminCount($group) <= 1
    ) {
      $form['description']['#markup'] = $this->t('You cannot remove this user as they are the only org admin for this group. Create the other user first, then remove this user from the group!');
      unset($form['actions']);
    }
  }

  /**
   * Disable form if there is only one org admin for the group.
   *
   * NOTE: To fake a failure as Behat, the stripe ID must be
   * BEHAT_FAIL --AND-- the user email must be stripe-admin@example.com.
   */
  private function disableIfOrgMemberIsStripeEmail(&$form) {
    $parameters = \Drupal::routeMatch()->getParameters();
    // Get user email.
    $user = $parameters->get('group_content')->getEntity();
    $userEmail = $user->getEmail();

    // Get ORG stripe email and stripe ID.
    $group = $parameters->get('group');
    $stripe_id = ValueFetcher::getFirstValue($group, 'field_stripe_customer_id');

    // Custom added orgs or migrated ORGS may not have a STRIPE ID.
    if ($stripe_id) {
      $behat_fail = FALSE;

      switch ($stripe_id) {
        case "BEHAT_PASS":
          return;
          break;
        case "BEHAT_FAIL":
          if ($userEmail == 'stripe-admin@example.com') {
            $behat_fail = TRUE;
          }
          break;
        default:
          // Fetch orgStripeEmail for comparison.
          $orgStripeEmail = FALSE;
          // @todo IMPROVE? Not ideal to set API key first then call service.
          PaymentConfig::setApiKey();
          $customer_resource = \drupal::service('atdove_billing.customer_resource');
          $customer = $customer_resource->getCustomerByID($stripe_id);
          if ($customer) {
            $orgStripeEmail = $customer->email;
          }
      }

      // See if we are maybe trying to delete a user associated with an org stripe ID.
      if (
        $behat_fail
        || $userEmail == $orgStripeEmail
      ) {
        $form['description']['#markup'] = $this->t("<strong>You cannot remove this user as their email is currently associated with this Organization's stripe account</strong>.<br/>To delete this user you must first add another org admin to this group, then change the Stripe email address associated with this organization to the new org admin's email, and then you can remove this org admin from the group.");
        unset($form['actions']);
      }
    }
  }

  /**
   * Disable form if the user is an org admin w/ stripe email for any group.
   */
  private function disableIfAnyOrgAdminAndStripeEmail(&$form) {
    $orgsWhereUserIsStripeOrgAdmin = [];

    $parameters = \Drupal::routeMatch()->getParameters();
    // Get user email.
    $user = $parameters->get('user');
    $userEmail = $user->getEmail();

    // Load customer resource for checking groups for stripe emails.
    PaymentConfig::setApiKey();
    $customer_resource = \drupal::service('atdove_billing.customer_resource');

    // Get all groups the user is a member of.
    $groups = array();
    $grp_membership_service = \Drupal::service('group.membership_loader');
    $group_memberships = $grp_membership_service->loadByUser($user);

    // Iterate over groups.
    foreach ($group_memberships as $group_membership) {
      // We only care if the user is an org admin in any particular group.
      if (array_key_exists('organization-admin', $group_membership->getRoles())) {
        // Load the group to get stripe ID.
        $group = $group_membership->getGroup();
        $stripe_id = ValueFetcher::getFirstValue($group, 'field_stripe_customer_id');

        // Get the stripe customer account email association.
        $customer = $customer_resource->getCustomerByID($stripe_id);

        if ($customer !== FALSE) {
          $orgStripeEmail = $customer->email;

          // If the users email matches, add them to the list.
          if ($userEmail == $orgStripeEmail) {
            $orgsWhereUserIsStripeOrgAdmin[] = $group;
          }
        }
      }
    }

    // If the user is an org admin in any group, say so and do not render form.
    if (count($orgsWhereUserIsStripeOrgAdmin) > 0) {
      $form['description']['#markup'] = $this->t("<strong>You cannot remove this user as their email is currently associated with an Organization's stripe account</strong>.<br/>To delete this user you must first add another org admin to the corresponding group, then change the Stripe email address associated with that organization to the new org admin's email, and then you can delete this user.");
      unset($form['actions']);
      unset($form['user_cancel_method']);
      unset($form['user_cancel_confirm']);
      unset($form['user_cancel_notify']);
      // @todo: If desired show a list of all the groups the user is an org admin in, utilize $orgsWhereUserIsStripeOrgAdmin.
    }
  }

}
