<?php

namespace Drupal\atdove_organizations_subgroups\Access;

use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\atdove_users\UsersManager;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\Entity\User;

/**
 * Access check for subgroup delete route/form.
 */
class SubgroupDeleteAccessCheck implements AccessInterface {

  /**
   * @inheritDoc
   */
  public function access(AccountProxy $account, GroupInterface $group) {
    // Verify that group is type organization.
    if ($group->getGroupType()->id() !== 'organization') {
      return AccessResult::forbidden('Group type must be organization.');
    }

    // Verify that current user is org admin of organization group
    // or user with privileged global role.
    if (UsersManager::userHasPrivilegedRole($account) || !OrganizationsManager::isUserOrgAdmin(User::load($account->id()), $group)) {
      return AccessResult::forbidden('Current user must have organization-admin role in organization group.');
    }

    // Verify that there are subgroups (organizational_groups) to delete.
    // Get all organizational_groups that are subgroups of the organization.
    $group_hierarchy_manager = \Drupal::service('ggroup.group_hierarchy_manager');
    $subgroups = $group_hierarchy_manager->getGroupSubgroups($group->id());
    // If none, redirect to subgroup create route.
    if (empty($subgroups)) {
      return AccessResult::forbidden('There must be at least one subgroup of organization group.');
    }

    return AccessResult::allowed();
  }
}
