<?php

namespace Drupal\atdove_opigno\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * A handler to provide a field that shows all of a users organizations.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_groups_views_field")
 */
class UserGroupsViewsField extends FieldPluginBase {

  /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  protected $currentDisplay;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->currentDisplay = $view->current_display;
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['hide_alter_empty'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $user = $values->_entity;
    $groups = [];
    $grp_membership_service = \Drupal::service('group.membership_loader');
    $grps = $grp_membership_service->loadByUser($user);
    $options = [];
    foreach ($grps as $grp) {
      $groups[] = $grp->getGroup();
    }
    foreach ($groups as $group) {
      if (($group->getGroupType()->id() == 'organizational_groups') || ($group->getGroupType()->id() == 'organization')) {
        $options[] = $group->label();
      }
    }
    $u_groups = implode(', ', $options);
    return $u_groups;
  }

}