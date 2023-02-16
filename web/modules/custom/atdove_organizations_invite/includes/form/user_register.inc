<?php

/**
 * @file
 * Contains hooks and functions related to the Drupal core user registration form and registration process.
 * New users accepting invitations to an organization via email will register using the core user registration form.
 * New users creating an account and organization will use the form provided by atdove_organizations module.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\group\Entity\Group;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Alter user registration form to prefill username and email address fields.
 * Sets username equal to email. This addresses a complaint AtDove had with the original site:
 * Usernames and email addresses should be the same thing. To solve this, we hide the username field
 * and automatically set it equal to the username field.
 */
function atdove_organizations_invite_form_user_register_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Pull email address from invite URL query parameter hash as done in ginvite module.
  // If query parameter doesn't exist, pull from tempstore set in
  // \Drupal\atdove_organizations_invite\Access\UserRegisterAccessCheck.
  $invitee_mail_encoded = \Drupal::request()->query->get('invitee_mail');
  if (!$invitee_mail_encoded) {
    $tempstore = \Drupal::service('tempstore.private');
    $store = $tempstore->get('atdove_organizations_invite');
    if (!empty($store)) {
      $invitee_mail_encoded = $store->get('invitee_mail');
    }
  }
  if (!empty($invitee_mail_encoded)) {
    $invitee_mail = _atdove_organizations_invite_decode_invite_hash($invitee_mail_encoded);
  }
  if (!empty($invitee_mail)) {
    if (\Drupal::service('email.validator')->isValid($invitee_mail)) {
      $form['account']['mail']['#default_value'] = $invitee_mail;
      $form['account']['name']['#default_value'] = $invitee_mail;
      $form['account']['name']['#access'] = FALSE;
    }
  }

  $form['actions']['submit']['#submit'][] = '_atdove_organizations_invite_user_register_form_submit';
}

/**
 * User register form submit handler.
 */
function _atdove_organizations_invite_user_register_form_submit(&$form, FormStateInterface &$form_state) {
  // Delete tempstore.
  $tempstore = \Drupal::service('tempstore.private');
  $store = $tempstore->get('atdove_organizations_invite');
  $store->delete('atdove_organizations_invite');
}

/**
 * Implements hook_ENTITY_TYPE_insert().
 *
 * When user entity is created.
 */
function atdove_organizations_invite_user_insert(EntityInterface $entity) {
  // When user is created (registers) and has an unaccepted invitation to
  // an organization group, accept the invitation automatically.
  $group_invitations = \Drupal::service('ginvite.invitation_loader')->loadByProperties(['invitee_mail' => $entity->get('mail')->getValue()[0]['value']]);
  if (!empty($group_invitations)) {
    foreach($group_invitations as $group_invitation) {
      if ($group_invitation->getGroupContent()->get('type')->getValue()[0]['target_id'] == 'organization-group_invitation' && !$group_invitation->getGroupContent()->get('invitation_status')->getValue()[0]['value']) {
        $group_content = $group_invitation->getGroupContent();
        $group = Group::load($group_content->get('gid')->getValue()[0]['target_id']);
        $group->addMember($entity);
        $group->save();
        // Assign group roles as specified by invitation.
        $group_roles = $group_content->get('group_roles')->getValue();
        foreach($group_roles as $group_role) {
          OrganizationsManager::grantUserOrgRole($entity, $group, $group_role['target_id']);
        }
        $group_content->set('invitation_status', 1);
        $group_content->save();
      }
    }
  }
}

/**
 * Implements hook_ENTITY_TYPE_insert().
 *
 * When group_content entity is updated.
 */
function atdove_organizations_invite_group_content_update(EntityInterface $entity) {
  // When invitation to organization group is accepted, assign user global active_org_member
  // role if organization has an active license.
  // @TODO: Eventually we may be able to replace this with an event subscriber:
  // https://www.drupal.org/project/group/issues/2844850
  if ($entity->get('type')->getValue()[0]['target_id'] == 'organization-group_invitation' && $entity->get('invitation_status')->getValue()[0]['value']) {
    $group_id = $entity->get('gid')->getValue()[0]['target_id'];
    $license_status = OrganizationsManager::getOrgLicenseStatusByID($group_id);
    if ($license_status == 'active') {
      $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail'=> $entity->get('invitee_mail')->getValue()[0]['value']]);
      // We can safely assume that only one user was returned because users cannot have the same email address.
      $user = reset($users);
      $user->addRole('active_org_member');
      // Unblock user.
      $user->activate();
      $user->save();

      // Notify user.
      \Drupal::messenger()->deleteAll();
      $group = Group::load($group_id);
      $org_name = $group->label();
      \Drupal::messenger()->addStatus(t("Welcome to AtDove! You have joined $org_name."));

    }
  }
}

/**
 * Decodes email address from invite hash query parameter exactly as done in ginvite module.
 * See: ginvite_form_user_register_form_alter()
 *
 * @param string $invitee_mail Encoded email address as encoded by ginvite module.
 * @return string Decoded email address.
 */
function _atdove_organizations_invite_decode_invite_hash($invitee_mail) {
  $search = ['-', '_'];
  $replace = ['+', '/'];
  return base64_decode(str_replace($search, $replace, $invitee_mail));
}
