<?php

namespace Drupal\atdove_organizations_invite\Access;

use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\atdove_users\UsersManager;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;
use Symfony\Component\Routing\Route;
use Drupal\group\Entity\GroupInterface;

/**
 * Checks access to group add content form.
 * For Organization group type, restricts access to
 * add members to only users with approved global roles
 * and restricts access to add member invitations to only
 * users with org admin role within group.
 *
 * Based on \Drupal\group\Access\GroupContentCreateAnyAccessCheck
 */
class GroupContentCreateAccessCheck implements AccessInterface {

  /**
   * Checks access for group content creation routes.
   *
   * All routes using this access check should have a group and plugin_id
   * parameter and have the _group_content_create_access requirement set to
   * either 'TRUE' or 'FALSE'.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group in which the content should be created.
   * @param string $plugin_id
   *   The group content enabler ID to use for the group content entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group, $plugin_id) {
    if ($group->getGroupType()->id() == 'organization') {
      switch ($plugin_id) {
        // Ensure that only a user with global admin role can
        // add members (as opposed to invite members).
        case 'group_membership':
          $user = User::load($account->id());
          // Check if user has an approved global role.
          if (UsersManager::userHasPrivilegedRole($user)) {
            return AccessResult::allowed();
          }
          return AccessResult::forbidden('Cannot view group membership without priveleged role');

        case 'group_invitation':
          $user = User::load($account->id());
          // Check if user has an approved global role.
          if (
            UsersManager::userHasPrivilegedRole($user)
            || OrganizationsManager::isUserOrgAdmin($user, $group)
          ) {
            if (
              UsersManager::userHasPrivilegedRole($user)
              || OrganizationsManager::groupHasMembershipLicensesLeft($group, TRUE)) {
              return AccessResult::allowed();
            }
            else {
              // t() just won't work here.
              $error_text = 'You are currently over the limit of users for this group and will need a larger plan.';
              \Drupal::messenger()->addError($error_text);
              return AccessResult::forbidden($error_text);
            }
          }

          return AccessResult::forbidden('User does not have an approved global role.');
      }
    }

    # You'd think this should return AccessResult::neutral(),
    # but in order to respect other access checks on this route,
    # we have to return allowed, and hope that the other access checks
    # return forbidden if appropriate.
    # See: https://www.drupal.org/docs/8/api/routing-system/access-checking-on-routes/route-access-checking-basics#s-multiple-access-checks
    return AccessResult::allowed();
  }
}
