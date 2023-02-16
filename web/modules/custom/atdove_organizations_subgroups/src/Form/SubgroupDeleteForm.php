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
 * Form to delete an organizational_groups subgroup
 * in the context of a parent organization group.
 */
class SubgroupDeleteForm extends FormBase {

  /**
   * @var \Drupal\group\Entity\GroupInterface The parent organization group.
   */
  private $group;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'atdove_organizations_subgroups_subgroup_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupInterface $group = NULL) {
    $this->group = $group;

    // Get all organizational_groups that are subgroups of the organization.
    $group_hierarchy_manager = \Drupal::service('ggroup.group_hierarchy_manager');
    $subgroups = $group_hierarchy_manager->getGroupSubgroups($group->id());
    // If none, redirect to subgroup create route.
    if (empty($subgroups)) {
      $Url = Url::fromRoute('entity.group_content.subgroup_add_form', ['group' => $group->id(), 'group_type' => 'organizational_groups'], ['absolute' => TRUE]);
      \Drupal::messenger()->addWarning($this->t('There are no organizational groups yet. You must first create one.'));
      return new RedirectResponse($Url->toString());
    }

    // Create options.
    $subgroup_options = [];
    foreach ($subgroups as $subgroup) {
      $subgroup_options[$subgroup->id()] = $subgroup->label();
    }
    asort($subgroup_options, SORT_NATURAL);

    $form['subgroup'] = [
      '#type' => 'checkboxes',
      '#options' => $subgroup_options,
      '#title' => $this->t('Organizational group(s)'),
      '#required' => TRUE,
      '#description' => $this->t('Choose the groups to delete. Any members of the group will remain members of your Organization.')
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete group(s)'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $subgroup_ids = $form_state->getValue('subgroup');
    $subgroup_ids = array_values($subgroup_ids);
    $message = '';

    foreach ($subgroup_ids as $key => $subgroup_id) {
      if ($subgroup_id === 0) {
        unset($subgroup_ids[$key]);
      }
    }

    foreach ($subgroup_ids as $key => $subgroup_id) {
      // Delete organizational_group.
      $subgroup = Group::load($subgroup_id);
      $subgroup->delete();

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

    if (count($subgroup_ids) == 1) {
      $message .= ' has been deleted.';
    }
    else {
      $message .= ' have been deleted.';
    }

    $this->messenger()->addStatus($this->t($message));

    $form_state->setRedirect('view.organization_members.page_1', ['group' => $this->group->id()]);
  }
}
