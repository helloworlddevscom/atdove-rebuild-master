<?php

namespace Drupal\atdove_organizations_subgroups\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form to remove a member to an organizational_groups subgroup
 * in the context of a parent organization group.
 */
class SubgroupRemoveMemberForm extends FormBase {

  /**
   * @var \Drupal\group\Entity\GroupInterface The parent organization group.
   */
  private $group;

  /**
   * @var \Drupal\Core\Session\AccountInterface The user to add to an organizational_groups.
   */
  private $user;

  /**
   * Constructs title of form.
   *
   * @param \Drupal\group\Entity\GroupInterface|null $group
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getFormTitle(GroupInterface $group, AccountInterface $user) {
    $username = $user->get('name')->getValue()[0]['value'];
    return $this->t("Remove $username from organizational group(s)");
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'atdove_organizations_subgroups_subgroup_remove_member';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupInterface $group = NULL, AccountInterface $user = NULL) {
    $this->group = $group;
    $this->user = $user;

    $username = $user->get('name')->getValue()[0]['value'];

    // Get all organizational_groups that are subgroups of the organization.
    $group_hierarchy_manager = \Drupal::service('ggroup.group_hierarchy_manager');
    $subgroups = $group_hierarchy_manager->getGroupSubgroups($group->id());
    // If none, redirect to organization group.
    if (empty($subgroups)) {
      $Url = Url::fromRoute('view.organization_members.page_1', ['group' => $this->group->id()]);
      \Drupal::messenger()->addError($this->t("There are no organizational groups yet. You must first create a new one."));
      return new RedirectResponse($Url->toString());
    }

    // Get all organizational_groups that user is a member of.
    $subgroups_is_member = [];
    foreach ($subgroups as $subgroup) {
      if ($subgroup->getMember($user)) {
        $subgroups_is_member[$subgroup->id()] = $subgroup;
      }
    }
    // If none, redirect to organization group.
    if (empty($subgroups_is_member)) {
      $Url = Url::fromRoute('view.organization_members.page_1', ['group' => $this->group->id()]);
      \Drupal::messenger()->addError($this->t("$username is not a member of an organizational group yet."));
      return new RedirectResponse($Url->toString());
    }

    // Create options.
    $subgroup_options = [];
    foreach ($subgroups_is_member as $subgroup_is_member) {
      $subgroup_options[$subgroup_is_member->id()] = $subgroup_is_member->label();
    }
    asort($subgroup_options, SORT_NATURAL);

    $form['subgroup'] = [
      '#type' => 'checkboxes',
      '#options' => $subgroup_options,
      '#title' => $this->t('Organizational group(s)'),
      '#required' => TRUE,
      '#description' => $this->t("Choose the groups to remove $username from.")
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove from group(s)'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $subgroup_ids = $form_state->getValue('subgroup');
    $subgroup_ids = array_values($subgroup_ids);
    $username = $this->user->get('name')->getValue()[0]['value'];
    $message = "$username has been removed from ";

    foreach ($subgroup_ids as $key => $subgroup_id) {
      if ($subgroup_id === 0) {
        unset($subgroup_ids[$key]);
      }
    }

    foreach ($subgroup_ids as $key => $subgroup_id) {
      // Remove member to group.
      $subgroup = Group::load($subgroup_id);
      $subgroup->removeMember($this->user);
      $subgroup->save();

      // Construct status message.
      $subgroup_label = $form["subgroup"]["#options"][$subgroup_id];
      if ($key == 0) {
        $message .= "$subgroup_label";
      }
      elseif ($key + 1 == count($subgroup_ids)) {
        $message .= " and $subgroup_label";
      }
      else {
        $message .= ", $subgroup_label";
      }
    }

    $message .= '.';
    $this->messenger()->addStatus($this->t($message));

    $form_state->setRedirect('view.organization_members.page_1', ['group' => $this->group->id()]);
  }
}
