<?php

namespace Drupal\atdove_organizations_invite\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Checks access to user register form.
 * Only allows access if URL query parameter or tempstore contains
 * a valid encoded email address corresponding to a ginvite group invitation.
 */
class UserRegisterAccessCheck implements AccessInterface {

  /**
   * Checks access to user register form.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account) {
    $user = User::load($account->id());

    // If user is not anonymous allow default redirect.
    if (!$user->isAnonymous()) {
      return AccessResult::neutral();
    }
    else {
      // Try to retrieve query parameter from URL.
      $invitee_mail_encoded = \Drupal::request()->query->get('invitee_mail');
      if (empty($invitee_mail_encoded)) {
        // On form submission this access check is run again, except this
        // time the parameter is stripped from the URL. To ensure that this
        // access check succeeds the second time, we have to pull the email from tempstore.
        $tempstore = \Drupal::service('tempstore.private');
        $store = $tempstore->get('atdove_organizations_invite');
        if (!empty($store)) {
          $invitee_mail_encoded = $store->get('invitee_mail');
        }
      }

      if (empty($invitee_mail_encoded)) {
        return AccessResult::forbidden('No invitee_mail query parameter in URL or in tempstore.');
      }
      else {
        // Decode invitee_mail to email exactly as done in ginvite_form_user_register_form_alter().
        $invitee_mail = _atdove_organizations_invite_decode_invite_hash($invitee_mail_encoded);
        if (!\Drupal::service('email.validator')->isValid($invitee_mail)) {
          return AccessResult::forbidden('Decoded email is not valid.');
        }
        else {
          $group_invitations = \Drupal::service('ginvite.invitation_loader')->loadByProperties(['invitee_mail' => $invitee_mail]);
          if (empty($group_invitations)) {
            return AccessResult::forbidden('No group_invitation entity found.');
          }
          else {
            // Save encoded email in tempstore to be used when this access
            // check is run again after form submission. This is also used
            // in atdove_organizations_invite_form_user_register_form_alter()
            // to set the value of the username and email address fields.
            $tempstore = \Drupal::service('tempstore.private');
            $store = $tempstore->get('atdove_organizations_invite');
            $store->set('invitee_mail', $invitee_mail_encoded);

            return AccessResult::allowed();
          }
        }
      }
    }
  }
}
