<?php

namespace Drupal\atdove_organizations_invite\Form;

use Drupal\ginvite\Form\BulkGroupInvitationConfirm as BaseBulkGroupInvitationConfirm;
use Drupal\Core\Url;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BulkGroupInvitationConfirm.
 * @package Drupal\atdove_organizations_invite\Form
 *
 * Extends form provided by ginvite module and overrides
 * method in order to send notification via Opigno's notification system.
 * See: Drupal\atdove_organizations_invite\Form\BulkGroupInvitationConfirm
 */
class BulkGroupInvitationConfirm extends BaseBulkGroupInvitationConfirm implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Notify existing users of invitation using Opigno's notification system.
    foreach ($this->tempstore['emails'] as $email) {
      $users = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->loadByProperties(['mail'=> $email]);

      foreach ($users as $user) {
        $destination = Url::fromRoute('view.my_organization_invitations.page_1', ['user' => $user->id()])->toString();
        opigno_set_message($user->id(), t('You have pending invitations.'), $destination);
      }
    }

    parent::submitForm($form, $form_state);
  }
}
