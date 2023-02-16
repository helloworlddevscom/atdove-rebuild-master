<?php

/**
 * @file
 * Contains hooks and functions related to the group invitation forms.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\Core\Render\Markup;

/**
 * Implements hook_form_alter().
 */
function atdove_organizations_invite_form_alter(&$form, $form_state, $form_id) {
  switch ($form_id) {
    // Multi-user invite form.
    case 'bulk_group_invitation':
      $path = \Drupal::request()->getpathInfo();
      $path_sections  = explode('/', $path);
      $org = Group::load($path_sections[2]);
      $org_title = $org->label();

      $form['#title'] = t("Invite users to $org_title");

      $form['email_address']['#title'] = Markup::create(t('Invitee Emails'));

      $form['actions']['submit']['#value'] = Markup::create(t('Invite Users'));
      $form['submit']['#value'] = Markup::create(t('Invite Users'));

      $form['actions']['submit_cancel']['#value'] = Markup::create(t('Cancel'));

      // Move cancel button to left of submit.
      $form['actions']['submit_cancel']['#weight'] = 0;
      $form['submit']['#weight'] = 1000;
      $form['actions']['submit']['#weight'] = 1000;

      // Add custom submit handler to cancel button.
      if (isset($form['actions']['submit_cancel']['#submit'])) {
        $form['actions']['submit_cancel']['#submit'][] = '_atdove_organizations_invite_invite_form_cancel';
      }
      else {
        $form['actions']['submit_cancel']['#submit'] = [
          '_atdove_organizations_invite_invite_form_cancel'
        ];
      }
      $form['actions']['submit_cancel']['#limit_validation_errors'] = [];

      break;

    // Single user invite form.
    case 'group_content_organization-group_invitation_add_form':
      $path = \Drupal::request()->getpathInfo();
      $path_sections  = explode('/', $path);
      $org = Group::load($path_sections[2]);
      $org_title = $org->label();

      $form['#title'] = t("Invite user to $org_title");

      $email_field_label = t('Invitee Email');
      $form['invitee_mail']['widget'][0]['value']['#title'] = $email_field_label;
      $form['invitee_mail']['widget'][0]['#title'] = $email_field_label;
      $form['invitee_mail']['widget']['#title'] = Markup::create($email_field_label);

      $form['group_roles']['widget']['#description'] = t('Optionally assign Admin role to user. We recommend only giving this role to trusted users. <br>They will have access to the billing information for your organization.');

      $form['actions']['submit']['#value'] = Markup::create(t('Invite User'));

      // Add custom submit handler.
      if (isset($form['actions']['submit']['#submit'])) {
        $form['actions']['submit']['#submit'][] = '_atdove_organizations_invite_invite_form_submit';
      }
      else {
        $form['actions']['submit']['#submit'] = [
          '_atdove_organizations_invite_invite_form_submit'
        ];
      }

      // Create cancel button with custom submit handler.
      $form['actions']['submit_cancel'] = [
        '#type' => 'submit',
        '#weight' => 0,
        '#value' => t('Cancel'),
        '#submit' => ['_atdove_organizations_invite_invite_form_cancel'],
        '#limit_validation_errors' => [],
      ];

      // Move cancel button to left of submit.
      $form['submit']['#weight'] = 1000;

      // Remove form-actions wrapper from around submit button.
      // This prevents the button from becoming full-width
      // due to CSS from Opigno Platon theme.
      unset($form['actions']['#type']);

      break;
  }
}

/**
 * Invite forms cancel submit handler.
 *
 * @param array $form
 * @param FormStateInterface $form_state
 */
function _atdove_organizations_invite_invite_form_cancel(array &$form, FormStateInterface &$form_state) {
  // Redirect to members listing page.
  $path = \Drupal::request()->getpathInfo();
  $path_sections = explode('/', $path);
  $org = Group::load($path_sections[2]);
  $url = Url::fromRoute('view.group_members.page_1', ['group' => $org->id()]);
  $form_state->setRedirectUrl($url);
}

/**
 * Invite forms submit handler.
 *
 * @param array $form
 * @param FormStateInterface $form_state
 */
function _atdove_organizations_invite_invite_form_submit(array &$form, FormStateInterface &$form_state) {
  // Notify existing users of invitation using Opigno's notification system.
  $message = t('You have pending invitations.');

  // NOTE: This had to be handled differently for the bulk invite form.
  // See: \Drupal\atdove_organizations_invite\Form\BulkGroupInvitationConfirm

  switch ($form['#form_id']) {
    // Single user invite form.
    case 'group_content_organization-group_invitation_add_form':
      $email = $form_state->getValue('invitee_mail')[0]['value'];
      $users = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->loadByProperties(['mail'=> $email]);

      foreach ($users as $user) {
        $destination = Url::fromRoute('view.my_organization_invitations.page_1', ['user' => $user->id()])->toString();
        opigno_set_message($user->id(), $message, $destination);
      }

      // Redirect to members listing page.
      $path = \Drupal::request()->getpathInfo();
      $path_sections = explode('/', $path);
      $org = Group::load($path_sections[2]);
      $url = Url::fromRoute('view.group_members.page_1', ['group' => $org->id()]);
      $form_state->setRedirectUrl($url);

      break;
  }
}
