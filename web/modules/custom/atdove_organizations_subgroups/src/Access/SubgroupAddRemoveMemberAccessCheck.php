<?php

namespace Drupal\atdove_organizations_subgroups\Access;

use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\atdove_users\UsersManager;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Access check for subgroup add/remove routes/forms.
 */
class SubgroupAddRemoveMemberAccessCheck implements AccessInterface {

  /**
   * @inheritDoc
   */
  public function access(AccountProxy $account, GroupInterface $group, UserInterface $user) {
    // Verify that group is type organization.
    if ($group->getGroupType()->id() !== 'organization') {
      return AccessResult::forbidden('Group type must be organization.');
    }

    // Verify that current user is org admin of organization group
    // or user with privileged global role.
    if (
      !UsersManager::userHasPrivilegedRole($account)
      && !OrganizationsManager::isUserOrgAdmin(User::load($account->id()), $group)
    ) {
      return AccessResult::forbidden('Current user must have organization-admin role in organization group.');
    }

    // Verify that user is a member of organization group.
    if (!$group->getMember($user)) {
      return AccessResult::forbidden('User must be a member of organization group.');
    }

    return AccessResult::allowed();
  }
}
