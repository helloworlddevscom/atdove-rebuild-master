<?php

namespace Drupal\atdove_organizations_invite\Controller;

use Drupal\ginvite\Controller\InvitationOperations as BaseInvitationOperations;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContentInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class InvitationOperations.
 * @package Drupal\atdove_organizations_invite\Controller
 *
 * Extends form provided by ginvite module and overrides
 * method in order to modify message and redirect.
 * See: Drupal\ginvite\Controller\InvitationOperations
 */
class InvitationOperations extends BaseInvitationOperations {

  /**
   * {@inheritdoc}
   */
  public function accept(Request $request, GroupContentInterface $group_content) {
    // We want to allow parent class method to run,
    // but afterward modify the message and redirect.
    // This way we don't have to duplicate any functionality provided
    // by the parent class method.
    parent::accept($request, $group_content);

    $group_id = $group_content->get('gid')->getValue()[0]['target_id'];
    $group = Group::load($group_id);
    $group_type = $group->getGroupType()->id();
    // Only modify message and redirect if group type is organization.
    if ($group_type == 'organization') {
      \Drupal::messenger()->deleteAll();

      $org_name = $group->label();
      \Drupal::messenger()->addStatus(t("You have joined $org_name. Welcome!"));

      return $this->redirect('entity.group.canonical', ['group' => $group_id]);
    }
  }
}
