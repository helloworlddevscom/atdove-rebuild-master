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
 * Form to add a member to an organizational_groups subgroup
 * in the context of a parent organization group.
 */
class SubgroupAddMemberForm extends FormBase {

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
    return $this->t("Add $username to organizational group(s)");
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'atdove_organizations_subgroups_subgroup_add_member';
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
    // If none, redirect to subgroup create route.
    if (empty($subgroups)) {
      $Url = Url::fromRoute('entity.group_content.subgroup_add_form', ['group' => $group->id(), 'group_type' => 'organizational_groups'], ['absolute' => TRUE]);
      \Drupal::messenger()->addWarning($this->t('Before adding a member to an organizational group, you must first create one.'));
      return new RedirectResponse($Url->toString());
    }

    // Get all organizational_groups that user is already a member of.
    $subgroups_already_member = [];
    foreach ($subgroups as $subgroup) {
      if ($subgroup->getMember($user)) {
        $subgroups_already_member[$subgroup->id()] = $subgroup;
      }
    }

    // Remove organizational_groups that user is already a member of.
    $avail_subgroups = array_diff_key($subgroups, $subgroups_already_member);
    // Check if there are any subgroups left and if not, redirect user to
    // group members route.
    if (empty($avail_subgroups)) {
      $Url = Url::fromRoute('view.organization_members.page_1', ['group' => $group->id()], ['absolute' => TRUE]);
      \Drupal::messenger()->addError($this->t("$username is already in every organizational group. You must first create a new one."));
      return new RedirectResponse($Url->toString());
    }

    // Create options.
    $subgroup_options = [];
    foreach ($avail_subgroups as $avail_subgroup) {
      $subgroup_options[$avail_subgroup->id()] = $avail_subgroup->label();
    }
    asort($subgroup_options, SORT_NATURAL);

    $form['subgroup'] = [
      '#type' => 'checkboxes',
      '#options' => $subgroup_options,
      '#title' => $this->t('Organizational group(s)'),
      '#required' => TRUE,
      '#description' => $this->t("Choose the groups to add $username to.")
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add to group(s)'),
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
    $message = "$username has been added to ";

    foreach ($subgroup_ids as $key => $subgroup_id) {
      if ($subgroup_id === 0) {
        unset($subgroup_ids[$key]);
      }
    }

    foreach ($subgroup_ids as $key => $subgroup_id) {
      // Add member to group.
      $subgroup = Group::load($subgroup_id);
      $subgroup->addMember($this->user);
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
