<?php

namespace Drupal\atdove_opigno\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filters by a user's organizational groups.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("cert_org_groups_views_filter")
 */
class CertOrgGroupsViewsFilter extends ManyToOne {

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

    $this->valueTitle = t('Filter by User Organizations');
    $this->definition['options callback'] = [$this, 'generateOptions'];
    $this->currentDisplay = $view->current_display;
  }


  protected function valueForm(&$form, FormStateInterface $form_state) {
   // $users = $this->value ? User::loadMultiple($this->value) : [];
    $default_value = [];
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Groups'),
      '#description' => $this->t('Enter a comma separated list of groups.'),
      '#target_type' => 'group',
      '#tags' => TRUE,
      '#multiple' => TRUE,
    ];

    $user_input = $form_state->getUserInput();
    // while (is_array($user_input)) {
    //   $user_input = $user_input ? reset($user_input) : '';
    // }
    if ($form_state->get('exposed') && !isset($user_input[$this->options['expose']['identifier']])) {
      //$user_input[$this->options['expose']['identifier']] = $default_value;
      $form_state->setUserInput($user_input);
    }
  }
  /**
   * Helper function that builds the query.
   */
  public function query() {
    if (!empty($this->value) && (!empty(reset($this->value)))) {
      $configuration = [
        'table' => 'group_content_field_data',
        'field' => 'entity_id',
        'left_table' => 'h5p_points',
        'left_field' => 'uid',
        'operator' => '=',
      ];

      $join = Views::pluginManager('join')->createInstance('standard', $configuration);
      $this->query->addRelationship('group_content_field_data', $join, 'h5p_points');
      $this->query->addWhere('AND', 'group_content_field_data.gid', $this->value, 'IN');
    }
    else {
       parent::query();
    }
  }
}