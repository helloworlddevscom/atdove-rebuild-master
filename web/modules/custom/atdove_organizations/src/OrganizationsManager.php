<?php
/**
 * @file
 * Contains \Drupal\atdove_organizations\OrganizationsManager.
 */

namespace Drupal\atdove_organizations;

use Drupal\atdove_utilities\ValueFetcher;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\Core\Link;
use Drupal\group\Entity\GroupContent;

/**
 * Class OrganizationsManager.
 *
 * Contains misc. methods for interacting with Organization Groups.
 */
class OrganizationsManager {

  // Establish a default membership limit to use if one cannot be determined.
  public static int $default_member_limit = 20;

  /**
   * Gets license status of an organization group.
   *
   * @param \Drupal\group\Entity\Group $org Organization group to get license status for.
   * @return string License status of the organization group.
   */
  public static function getOrgLicenseStatus(Group $org) {
    $status = 'inactive';
    if (!empty($org->get('field_license_status'))) {
      if (!empty($org->get('field_license_status')->getValue())) {
        $status = $org->get('field_license_status')->getValue()[0]['value'];
      }
    }

    return $status;
  }

  /**
   * Gets license status of an organization group by Group ID.
   *
   * @param string $org_id ID of an organization group.
   * @return string License status of the organization group.
   */
  public static function getOrgLicenseStatusByID(string $org_id) {
    $org = Group::load($org_id);
    return self::getOrgLicenseStatus($org);
  }

  /**
   * Gets organization groups a user belongs to.
   *
   * @param \Drupal\user\Entity\User $user User to get organization groups for.
   * @param int $limit Limit results to specific number of organization groups.
   * @return array Array of Drupal\group\Entity\Group organization groups the user belongs to.
   */
  public static function getUserOrgs(User $user = NULL, int $limit = null) {
    if (is_null($user)) {
      $user = \Drupal::currentUser();
    }

    $user_id = $user->id();
    $orgs = [];
    // Loading all group memberships in order to retrieve organization groups is extremely time consuming
    // for some users (user 1) who belong to thousands of groups, if you use the methods provided by group module.
    // This is why we use a static query here.
    $database = \Drupal::database();
    $query_str = "select * from group_content_field_data gcfd inner join groups g on g.id=gcfd.gid where g.type='organization' and gcfd.type='organization-group_membership' and gcfd.entity_id={$user_id} ORDER BY gcfd.created ASC";
    if ($limit) {
      $query_str .= " LIMIT {$limit}";
    }
    $query = $database->query($query_str);
    $group_memberships = $query->fetchAll();

    foreach ($group_memberships as $group_membership) {
      $orgs[] = Group::load($group_membership->gid);
    }

    return $orgs;
  }

  /**
   * Gets EXPIRED organization groups a user belongs to.
   *
   * @param \Drupal\user\Entity\User $user User to get organization groups for.
   *
   * @return array Array of Drupal\group\Entity\Group organization groups the user belongs to.
   */
  public static function getUserInactiveOrgs(User $user) {
    $user_id = $user->id();
    $orgs = [];
    // Loading all group memberships in order to retrieve organization groups is extremely time consuming
    // for some users (user 1) who belong to thousands of groups, if you use the methods provided by group module.
    // This is why we use a static query here.
    $database = \Drupal::database();
    $query_str = "
        select * from group_content_field_data gcfd
        inner join groups g on g.id=gcfd.gid
        inner join group__field_license_status gls on g.id=gls.entity_id
        where g.type='organization'
          and gcfd.type='organization-group_membership'
          and gcfd.entity_id={$user_id}
          and gls.field_license_status_value = 'inactive'
        ORDER BY gcfd.created ASC
    ";
    $query = $database->query($query_str);
    $group_memberships = $query->fetchAll();

    foreach ($group_memberships as $group_membership) {
      $orgs[] = Group::load($group_membership->gid);
    }

    return $orgs;
  }

  /**
   * Gets all roles that a user has in an organization group.
   *
   * @param \Drupal\user\Entity\User $user User to get roles for.
   * @param \Drupal\group\Entity\Group $org Organization group to get the users roles from.
   * @return array Array of Drupal\group\Entity\GroupRole objects.
   */
  public static function getUserOrgRoles(User $user, Group $org) {
    $group_membership = $org->getMember($user);
    if ($group_membership) {
      return $group_membership->getRoles();
    }
    else {
      return [];
    }
  }

  /**
   * Grants a group level role for a user within an organization group.
   *
   * @param \Drupal\user\Entity\User $user User to grant role to.
   * @param \Drupal\group\Entity\Group $org Organization group in which to grant user role.
   * @param string $role Group level role to grant user.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function grantUserOrgRole(User $user, Group $org, string $role) {
    $group_membership = $org->getMember($user);
    if ($group_membership) {
      $group_content = $group_membership->getGroupContent();
      $group_content->group_roles->target_id = $role;
      $group_content->save();
    }
  }

  /**
   * Grants global role indicating membership in active organization group.
   *
   * @param \Drupal\user\Entity\User $user User to grant role to.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function grantActiveOrgMemberRole(User $user) {
    $user->addRole('active_org_member');
    $user->save();
  }

  /**
   * Revokes global role indicating membership in active organization group.
   *
   * @param \Drupal\user\Entity\User $user User to revoke role to.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function revokeActiveOrgMemberRole(User $user) {
    $user->removeRole('active_org_member');
    $user->save();
  }

  /**
   * Check if a user has an active organization associated with them.
   *
   * In most cases it would be quicker to use:
   * \Drupal\atdove_users\UsersManager::userHasActiveOrgMemberRole
   *
   * @param UserInterface $user
   * User object to search for active org association.
   *
   * @return bool
   *   TRUE If the user has an active org, FALSE if no active orgs.
   */
  public static function userHasAnActiveOrg(UserInterface $user) {
    $orgs = self::getUserOrgs($user);

    // Determine if user belongs to at least one active org.
    foreach ($orgs as $org) {
      // Check if license is active.
      $status = self::getOrgLicenseStatus($org);
      if ($status == 'active') {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Verifies that a user belongs to an organization group with an active license
   * and potentially grants/revokes active_org_member global role to/from user.
   *
   * This also will display a message relating to any inactive organization
   * memberships.
   *
   * Calling this method should only be done in strategic places.
   * If you need to check whether a user belongs to an organization with an active
   * license, please use either:
   * \Drupal\atdove_users\UsersManager::userHasActiveOrgMemberRole
   * or
   * \Drupal\atdove_organizations\OrganizationsManager::userHasAnActiveOrg
   *
   * @param \Drupal\user\Entity\User $user
   *   User to verify.
   * @param boolean $access_check
   *   Determines whether to return as an access check or not.
   */
  public static function verifyOrgAdminAnyGroup(User $user, $access_check = true) {
    $orgs = self::getUserOrgs($user);

    // Determine if user belongs to at least one active org.
    $active_org = false;
    foreach ($orgs as $org) {
      // Check if license is active.
      $status = self::getOrgLicenseStatus($org);
      if ($status == 'active') {
        $active_org = true;
      }
      elseif ($notify) {
        // If license is not active, display error message
        // depending on user group role.
        $title = $org->label();
        if (self::isUserOrgAdmin($user, $org)) {
          $message = t("The subscription for your organization @orgname has expired. Please renew your subscription @link.", [
            '@orgname' => $title,
            '@link' => link::fromTextAndUrl(t('here'), Url::fromUserInput('/group/' . $org->id() . '/manage-billing'))->toString(),
          ]);
          \Drupal::messenger()->addError($message);
          // @TODO: Notify via Opigno notification as well.
        }
        else {
          \Drupal::messenger()->addError(t("The license for your organization $title has expired. Please contact your Organization Admin."));
          // @TODO: Notify via Opigno notification as well.
        }
      }
    }

    // If user belongs to at least one active org, grant user global role.
    if ($active_org) {
      if (!$user->hasRole('active_org_member')) {
        self::grantActiveOrgMemberRole($user);
      }
    }
    else {
      // If user does not belong to at least one active org, revoke global role.
      if ($user->hasRole('active_org_member')) {
        self::revokeActiveOrgMemberRole($user);
      }
    }
  }

  /**
   * Verifies that a user belongs to an organization group with an active license
   * and potentially grants/revokes active_org_member global role to/from user.
   *
   * This also will display a message relating to any inactive organization
   * memberships.
   *
   * Calling this method should only be done in strategic places.
   * If you need to check whether a user belongs to an organization with an active
   * license, please use either:
   * \Drupal\atdove_users\UsersManager::userHasActiveOrgMemberRole
   * or
   * \Drupal\atdove_organizations\OrganizationsManager::userHasAnActiveOrg
   *
   * @param \Drupal\user\Entity\User $user
   *   User to verify.
   * @param boolean $notify
   *   Whether to notify user if they have an org without an active license.
   */
  public static function verifyActiveOrgMember(User $user, $notify = true) {
    $orgs = self::getUserOrgs($user);

    // Determine if user belongs to at least one active org.
    $active_org = false;
    foreach ($orgs as $org) {
      // Check if license is active.
      $status = self::getOrgLicenseStatus($org);
      if ($status == 'active') {
        $active_org = true;
      }
      elseif ($notify) {
        // If license is not active, display error message
        // depending on user group role.
        $title = $org->label();
        if (self::isUserOrgAdmin($user, $org)) {
          $message = t("The subscription for your organization @orgname has expired. Please renew your subscription @link.", [
            '@orgname' => $title,
            '@link' => link::fromTextAndUrl(t('here'), Url::fromUserInput('/group/' . $org->id() . '/manage-billing'))->toString(),
          ]);
          \Drupal::messenger()->addError($message);
          // @TODO: Notify via Opigno notification as well.
        }
        else {
          \Drupal::messenger()->addError(t("The license for your organization $title has expired. Please contact your Organization Admin."));
          // @TODO: Notify via Opigno notification as well.
        }
      }
    }

    // If user belongs to at least one active org, grant user global role.
    if ($active_org) {
      if (!$user->hasRole('active_org_member')) {
        self::grantActiveOrgMemberRole($user);
      }
    }
    else {
      // If user does not belong to at least one active org, revoke global role.
      if ($user->hasRole('active_org_member')) {
        self::revokeActiveOrgMemberRole($user);
      }
    }
  }

  /**
   * Creates a new organization group and assigns an owner.
   *
   * @param \Drupal\user\Entity\User $user User who should be the owner of the group.
   * @param string $org_name Name of the organization.
   * @param array $values Key value pair to set field values.
   * @return \Drupal\group\Entity\Group Newly created organization group.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function createOrg(User $user, string $org_name, array $values = []) {
    $new_org = \Drupal::entityTypeManager()->getStorage('group')->create(['type' => 'organization']);
    $new_org->set('label', $org_name);

    // Set field values on the org.
    foreach ($values as $field => $value) {
      $new_org->set($field, $value);
    }

    $new_org->setOwner($user);
    $new_org->save();
    $new_org->addMember($user);
    $new_org->save();

    return $new_org;
  }

  /**
   * Gets \Drupal\Core\Url object to an organization group.
   *
   * @param \Drupal\group\Entity\Group $org Organization group to get Url object to.
   * @return \Drupal\Core\Url Url object to organization group.
   */
  public static function getOrgUrl(Group $org) {
    return Url::fromRoute('entity.group.canonical', ['group' => $org->id()]);
  }

  /**
   * Checks whether user has organization-admin role within an organization group.
   *
   * @param \Drupal\user\Entity\User $user User whose role should be checked.
   * @param \Drupal\group\Entity\Group $org Organization group to check within.
   * @return bool True if user has organization-admin role. False if not.
   */
  public static function isUserOrgAdmin(User $user, Group $org) {
    $roles = self::getUserOrgRoles($user, $org);
    $org_admin = false;
    foreach ($roles as $role) {
      if ($role->id() == 'organization-admin') {
        $org_admin = true;
      }
    }
    return $org_admin;
  }

  /**
   * Returns whether a group has membership licenses left.
   *
   * @param \Drupal\group\Entity\Group $group
   *  A fully loaded group object
   * @param bool $boolean
   *   Optional. TRUE if you want this method to return T/F
   *
   * @return bool|AccessResult
   *
   */
  public static function groupHasMembershipLicensesLeft(Group $group, bool $boolean = FALSE) {
    $overOrAtUserLimit = count($group->getMembers())
      >=
      ValueFetcher::getFirstValue($group, 'field_member_limit')
    ;

    if ($overOrAtUserLimit) {
      return $boolean ? FALSE : AccessResult::forbidden('Group does not have membership licenses left');
    }

    return $boolean ? TRUE : AccessResult::allowed();
  }

  /**
   * Returns whether a user is an org admin in any group or not.
   *
   * @param bool $returnAsAccessResult
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   */
  public static function currentUserIsOrgAdminInAnyGroup(bool $returnAsAccessResult = TRUE) {
    $user = \Drupal::currentUser();

    // Anonymous user is not an org admin in any group.
    if ($user->isAnonymous()) {
      return $returnAsAccessResult ? AccessResult::forbidden('Anonymous users are not an org admin for sure.') : FALSE;
    }

    // Super admin does whatever superadmin wants.
    if ($user->id() == 1) {
       return $returnAsAccessResult ? AccessResult::allowed() : TRUE;
    }

    // Now let's see if the user is an admin in any group.
    $query = \Drupal::entityQuery('group_content')
      ->condition('type', 'organization-group_membership')
      ->condition('entity_id', $user->id())
      ->condition('group_roles', 'organization-admin');
    ;
    $results = $query->execute();

    if (empty($results)) {
      return $returnAsAccessResult ? AccessResult::forbidden('Current user is not an org admin in any groups.') : FALSE;
    }

    return $returnAsAccessResult ? AccessResult::allowed() : TRUE;
  }

  /**
   * Returns the count of org admins in a given group.
   *
   * @param $group
   *   The group you want an org admin count for.
   *
   * @return int
   *   Amount of org admins the group has.
   */
  public static function orgAdminCount($group) : int {
    $count = 0;

    $members = $group->getMembers();
    foreach ($members as $member) {
      if (array_key_exists('organization-admin', $member->getRoles())) {
        $count++;
      }
    }

    return $count;
  }

  /**
   * Checks whether user is an org admin of any parent group
   *
   * @param \Drupal\user\Entity\User $user User whose role should be checked.
   * @param \Drupal\group\Entity\Group $org Organization group to check within.
   * @return bool True if user has organization-admin role. False if not.
   */
  public static function isUserOrgAdminOfParentGroup(User $user, Group $org) {
    // Check if a user is an org admin in any parent group of subgroup.
    $groupHierarchymanager = \Drupal::service('ggroup.group_hierarchy_manager');

    $parent_groups = $groupHierarchymanager->getGroupSupergroups($org->id());

    if (!empty($parent_groups)) {
      foreach ($parent_groups as $parent_group) {
        if (OrganizationsManager::isUserOrgAdmin($user, $parent_group)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Returns whether a user is an org admin in the current LEARNING PATH group.
   *
   * @param bool $returnAsAccessResult
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   */
  public static function currentUserIsOrgAdminInCurrentGroup(bool $returnAsAccessResult = TRUE)
  {

    // This should only be checking for learning path group members. ALl other group members pages will get neutral.

    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $current_path = \Drupal::service('path.current')->getPath();
    $path_args = explode('/', $current_path);
    $group = Group::load($path_args[2]);

    if (is_null($group)) {
      $group = \Drupal::routeMatch()->getParameter('group');
      if ($group && !$group instanceof Group) {
        $group = Group::load($group);
      }
    }
    $entity = $group;

    if (!is_null($entity)) {
      $entity_type_id = $entity->getEntityTypeId();
    }

    // If this is a learning path
    if (in_array($entity_type_id, ['group'], TRUE) && $entity->getGroupType()->id() == 'learning_path') {
      // Super admin does whatever superadmin wants.
      $roles = $user->getRoles();
      if ($user->id() == 1 || in_array('administrator', $roles)) {
        return $returnAsAccessResult ? AccessResult::allowed() : TRUE;
      }
      // Anonymous user is not an org admin in any group.
      if ($user->isAnonymous()) {
        return $returnAsAccessResult ? AccessResult::forbidden() : FALSE;
      }

      $user_orgs = OrganizationsManager::getUserOrgs();
      //Find group that user is org admin in
      if ($user_orgs != NULL) {
        foreach ($user_orgs as $org) {
          if (OrganizationsManager::isUserOrgAdmin($user, $org)) {
            $admin_org_ids[] = $org->id();
          }
        }
      }
      // Check if this learning path is a subgroup of this organization
      if (!empty($admin_org_ids)) {
        $ids = \Drupal::entityQuery('group_content')
          ->condition('entity_id', $entity->id())
          ->execute();
        $relations = \Drupal\group\Entity\GroupContent::loadMultiple($ids);
        foreach ($relations as $rel) {
          if ($rel->getEntity()->getEntityTypeId() == 'group') {
            $group_ids[] = $rel->getGroup()->id();
          }
        }
        if (isset($group_ids)) {

          // There might be more than 1 group in which the user is an admin.
          foreach ($group_ids as $gid) {
            if (in_array($gid, $admin_org_ids)) {
              return $returnAsAccessResult ? AccessResult::allowed() : TRUE;
            } else {
              return $returnAsAccessResult ? AccessResult::forbidden() : FALSE;
            }
          }
        }
      }
    } else {
      return AccessResult::allowed();
    }
  }

  /**
   * @param string $stripe_id
   * @param string $stripe_license_tier
   * @param string $stripe_plan_nickname
   * @return int
   */
  public static function discernMemberLimit(string $stripe_id, string $stripe_license_tier, string $stripe_plan_nickname)
  {
    $group_membership_limit = OrganizationsManager::$default_member_limit;
    $title_intval = intval($stripe_plan_nickname);
    $license_tier_intval = intval($stripe_license_tier);

    // Default to the price nickname as it's currently more reliable than the subscription's metadata license_tier value.
    // This is because it's possible to change the product/price on a subscription via the Stripe dashboard without updating
    // the license_tier meta value.
    // todo: add logic to update subscription metadata when the product is changed on a subscription.  Created ticket
    // created ticket https://helloworlddevs.atlassian.net/browse/AR-598 to address.
    if (
      is_int($title_intval)
      && $title_intval > 0
    ) {
      $group_membership_limit = $title_intval;
    } elseif (
      is_int($license_tier_intval)
      && $license_tier_intval > 0
    ) {
      $group_membership_limit = $license_tier_intval;
    } else {
      \Drupal::logger('atdove_billing')->warning('ExistingOrgSubscribe:submitForm was unable to discern a member limit. Set to default of @default for stripe ID @stripe_id',
        [
          '@default' => OrganizationsManager::$default_member_limit,
          '@stripe_id' => $stripe_id
        ]
      );
    }

    return $group_membership_limit;

  }

}
