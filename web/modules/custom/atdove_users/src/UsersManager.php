<?php
/**
 * @file
 * Contains \Drupal\atdove_users\UsersManager.
 */

namespace Drupal\atdove_users;

use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountProxy;
use InvalidArgumentException;

/**
 * Class UsersManager.
 *
 * Contains misc. methods for interacting with Users.
 */
class UsersManager {

  /**
   * Return whether a user has invitations or not.
   *
   * @param null $user
   *   A user object, or null to load current user.
   *
   * @return bool
   *   TRUE if the user has invitations, FALSE if no invitations.
   */
  public static function userHasInvitations($user = NULL) {
    if (
      !$user instanceof AccountProxy
      && !$user instanceof User
    ) {
      if ($user === NULL) {
        $user = \Drupal::currentUser();
      }
      else {
        throw new InvalidArgumentException("\Drupal\atdove_users\UsersManager::userHasInvitations received a value other than NULL which was not an AccountProxy or User.");
      }
    }

    $invitations = \Drupal::entityTypeManager()
      ->getStorage('group_content')
      ->loadByProperties(
        [
          'type' => 'organization-group_invitation',
          'entity_id' => $user->id(),
        ]
      );

    return (count($invitations) > 0);

  }

  /**
   * Determines if a user (or current user if none provided) has active_org_member role.
   *
   * @param null|AccountProxy|User $user
   *   A fully qualified user, an account proxy object, or NULL to load current.
   *
   * @return bool
   *   T/F for whether a user has active_org_member role or not.
   */
  public static function userHasActiveOrgMemberRole($user = NULL) {
    if (
      !$user instanceof AccountProxy
      && !$user instanceof User
    ) {
      if ($user === NULL) {
        $user = \Drupal::currentUser();
      }
      else {
        throw new InvalidArgumentException("\Drupal\atdove_users\UsersManager::userHasActiveOrgMemberRole received a value other than NULL which was not an AccountProxy or User.");
      }
    }

    return
      !empty(array_intersect(['active_org_member'], $user->getRoles())) && !$user->isAnonymous();
  }

  /**
   * Determines if a user (or current user if none provided) has privileged role.
   *
   * @param null|AccountProxy|User $user
   *   A fully qualified user, an account proxy object, or NULL to load current.
   *
   * @return bool
   *   T/F for whether a user has privileged role or not.
   */
  public static function userHasPrivilegedRole($user = NULL) {
    $privileged_roles = [
      'administrator',
      'billing_admin',
    ];

    if (
      !$user instanceof AccountProxy
      && !$user instanceof User
    ) {
      if ($user === NULL) {
        $user = \Drupal::currentUser();
      }
      else {
        throw new InvalidArgumentException("\Drupal\atdove_users\UsersManager::userHasPrivilegedRole received a value other than NULL which was not an AccountProxy or User.");
      }
    }

    if ($user->id() == 1) {
      return TRUE;
    }

    return
      !empty(array_intersect($privileged_roles, $user->getRoles()));
  }
}
