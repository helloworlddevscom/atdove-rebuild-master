<?php

namespace Drupal\atdove_opigno\Access;

use Drupal\atdove_users\UsersManager;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;
use Symfony\Component\Routing\Route;

/**
 * Custom access check for opigno_statistics.user_achievements_page route.
 */
class UserAchievementsPageAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\user\Entity\User $user
   *   The currently logged in user.
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, User $user) {
    if (UsersManager::userHasActiveOrgMemberRole($user) || UsersManager::userHasPrivilegedRole($user)) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden('User does not have an approved global role.');
    }
  }
}
