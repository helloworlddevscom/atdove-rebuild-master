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
 * @ViewsFilter("user_groups_views_filter")
 */
class UserGroupsViewsFilter extends ManyToOne {

  /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  protected $currentDisplay;

  //protected $alwaysMultiple = TRUE;
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
    $default_value = [];
    $form['value'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Groups'),
      '#description' => $this->t('Enter a comma separated list of groups.'),
      '#target_type' => 'group',
      '#selection_settings' => array(
        'target_bundles' => array('organizational_groups', 'organization'),
      ),
      '#tags' => TRUE,
      '#multiple' => FALSE,
    ];

    $user_input = $form_state->getUserInput();

    if ($form_state->get('exposed') && !isset($user_input[$this->options['expose']['identifier']])) {
      $form_state->setUserInput($user_input);
    }
  }

  /**
   * Helper function that builds the query.
   */
  public function query() {
    if (!empty($this->value[0]['target_id'])) {
      $configuration = [
        'table' => 'group_content_field_data',
        'field' => 'entity_id',
        'left_table' => 'users_field_data',
        'left_field' => 'uid',
        'operator' => '=',
      ];

      $join = Views::pluginManager('join')->createInstance('standard', $configuration);
      $this->query->addRelationship('group_content_field_data', $join, 'users_field_data');
      $this->query->addWhere('AND', 'group_content_field_data.gid', (int)$this->value[0]['target_id'], 'IN'); 
    }
    else {
       parent::query();
    }
  }

}