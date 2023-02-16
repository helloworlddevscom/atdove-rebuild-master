<?php

namespace Drupal\atdove_organizations_subgroups\Access;

use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\atdove_users\UsersManager;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;
use Symfony\Component\Routing\Route;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\Group;

/**
 * Checks access to group add content form.
 * For organizational_groups group type, restricts access to
 * add members to only users with approved global roles.
 *
 * Based on \Drupal\group\Access\GroupContentCreateAnyAccessCheck
 */
class GroupContentAccessCheck implements AccessInterface {

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
  public function access(Route $route, AccountInterface $account, GroupInterface $group, $plugin_id = NULL) {
    // If we're dealing with a "sub group" organizational group.
    if ($group->getGroupType()->id() == 'organizational_groups') {
      switch ($plugin_id) {

        // We're dealing with a "group membership" group content type. IE: CRUDing users relative to organizaitonal groups.
        case 'group_membership':
          $user = User::load($account->id());
          // Check if user has an approved global role.
          if (
            UsersManager::userHasPrivilegedRole($user)
          ) {
            return AccessResult::allowed();
          }

          // Check if a user is an org admin in any parent group of subgroup.
          if (
            OrganizationsManager::isUserOrgAdminOfParentGroup($user, $group)
          ) {
            return AccessResult::allowed();
          }

          return AccessResult::forbidden('User is not approved to add group members to org groups.');
          break;
      }
    }

    # You'd think this should return AccessResult::neutral(),
    # but in order to respect other access checks on this route,
    # we have to return allowed, and hope that the other access checks
    # return forbidden if appropriate.
    # See: https://www.drupal.org/docs/8/api/routing-system/access-checking-on-routes/route-access-checking-basics#s-multiple-access-checks
    return AccessResult::allowed();
  }

  /**
   * This alters ALL group content add forms, and we've removed the group permissions checking from them.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function gcontentRouteCustomCreateAcccessCheck(Route $route, AccountInterface $account, GroupInterface $group,  $plugin_id) {

    // Priveleged users can do anything.
    if (UsersManager::userHasPrivilegedRole($account)) {
      return AccessResult::allowed();
    }

    // Perform initial basic route access check just as in _group_content_create_access IE: Drupal\group\Access::access
    $group_membership = $group->getMember($account);

    if ($group_membership) {
      // We can only get the group content type ID if the plugin is installed.
      if ($group->getGroupType()->hasContentPlugin($plugin_id)) {
        // Determine whether the user can create group content using the plugin.
        $group_content_type_id = $group->getGroupType()->getContentPlugin($plugin_id)->getContentTypeConfigId();
        $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler('group_content');
        $access = $access_control_handler->createAccess($group_content_type_id, $account, ['group' => $group]);

        // Only allow access if the user can create group content using the
        // provided plugin or if he doesn't need access to do so.
        if ($access) {
          return AccessResult::allowed();
        }
      }
    }

    // No basic perm, see if they get by as org admin on organizaitonal group.
    if (
      $group->getGroupType()->id() == 'organizational_groups'
      && OrganizationsManager::isUserOrgAdminOfParentGroup(user::load($account->id()), $group)
    ) {
      return AccessResult::allowed();
    }

    // No reasons for access occur. Let's return FORBIDDEN!
    return AccessResult::forbidden('gcontentRouteCustomCreateAcccessCheck access forbidden result');
  }
}
