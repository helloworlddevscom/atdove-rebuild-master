<?php

namespace Drupal\atdove_organizations_invite\Access;

use Drupal\group\Entity\Access\GroupAccessControlHandler as BaseGroupAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 * Extends the default access handler/controller for the Group entity.
 * The reason we're doing this is kind of silly. The Organizations Invitations view
 * (my_organization_invitations) will not display the name of the Group the invitation
 * is for because by default the user does not have access to view the Group. Drupal does not
 * have a concept of viewing the name of an entity without viewing the content. So this
 * actually grants the invitee user full view access to the Group. That is more than we want
 * to grant them, but hopefully it won't be an issue.
 *
 * @see \Drupal\group\Entity\Group.
 */
class GroupAccessControlHandler extends BaseGroupAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $access_result = parent::checkAccess($entity, $operation, $account);

    // If group type is organization, operation is view, group is published, and parent method did not return allowed,
    // check if user has an invitation to the group. If so, allow access.
    if ($entity->getGroupType()->id() == 'organization' && $operation == 'view' && $entity->isPublished() && !is_a($access_result, 'Drupal\Core\Access\AccessResultAllowed')) {
      $user = User::load($account->id());
      if ($user->isAuthenticated()) {
        $email = $user->get('mail')->getValue()[0]['value'];
        $group_invitation = \Drupal::service('ginvite.invitation_loader')->loadByProperties(['gid' => $entity->id(), 'invitee_mail' => $email]);
        if (!empty($group_invitation)) {
          return AccessResult::allowed();
        }
      }
    }

    return $access_result;
  }
}
