<?php

namespace Drupal\atdove_opigno\Plugin\EntityReferenceSelection;

use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection;
use Drupal\atdove_users\UsersManager;

/**
 * Provides specific access control for the node entity type.
 *
 * @EntityReferenceSelection(
 *   id = "atdove:users_of_current_user_groups",
 *   label = @Translation("Users of the current users group's only."),
 *   entity_types = {"users"},
 *   group = "atdove",
 *   weight = 3
 * )
 */
class UsersOfCurrentUserGroupsOnly extends NodeSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // UIDs to restrict by.
    $uids = [];

    // Restrict available UIDs for non privleged users.
    if (!UsersManager::userHasPrivilegedRole()) {

      $user_orgs = OrganizationsManager::getUserOrgs();

      // Iterate over users orgs to get all availabl emembers.
      foreach ($user_orgs as $user_org) {
        $members = $user_org->getMembers();
        foreach ($members as $member) {
          $uids[] = $member->getUser()->id();
        }
      }
    }

    // Check if we were passed a first and last name.
    if (count(explode(' ', $match)) == 2) {
      $exploded_match = explode(' ', $match);
      // Look for match in username IE email, or first/last name.
      $userNameMatching = $query->andConditionGroup()
        ->condition('field_first_name', $exploded_match[0], 'CONTAINS')
        ->condition('field_last_name', $exploded_match[1], 'CONTAINS');
    }
    else {
      // Look for match in username IE email, or first/last name.
      $userNameMatching = $query->orConditionGroup()
        ->condition('name', $match, 'CONTAINS')
        ->condition('field_first_name', $match, 'CONTAINS')
        ->condition('field_last_name', $match, 'CONTAINS');
    }

    $query->condition($userNameMatching);

    // Skip anonymous and super admin reccomendations.
    $query->condition('uid', 1, '>');

    if (!empty($uids)) {
      $query->condition('uid', $uids, 'IN');
    }

    return $query;
  }

}
