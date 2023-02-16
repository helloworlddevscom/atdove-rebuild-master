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
use Drupal\views\Plugin\views\filter\FilterPluginBase;
/**
 * Filters by the status of Certificates.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("atdove_certificate_views_filter")
 */
class AtdoveCertificateViewsFilter extends FilterPluginBase {

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
    $this->currentDisplay = $view->current_display;
        $this->definition['options callback'] = [$this, 'generateOptions'];

 
  }

  /**
   * Helper function that generates the options.
   * @return array
   */
  public function generateOptions() {
    // Array keys are used to compare with the table field values.
    return array(
      'passed' => 'passed',
      'failed' => 'failed',
    );
  }

  /**
   * Helper function that builds the query.
   */
  public function query() {

    $configuration2 = [
      'table' => 'opigno_activity__opigno_h5p',
      'field' => 'opigno_h5p_h5p_content_id',
      'left_table' => 'h5p_points',
      'left_field' => 'content_id',
      'operator' => '=',
    ];
    $configuration1 = [
      'table' => 'opigno_activity__field_opigno_quiz',
      'field' => 'entity_id',
      'left_table' => 'opigno_activity__opigno_h5p',
      'left_field' => 'entity_id',
      'operator' => '=',
    ];

    $join1 = Views::pluginManager('join')->createInstance('standard', $configuration1);
    $join2 = Views::pluginManager('join')->createInstance('standard', $configuration2);

    $this->query->addRelationship('opigno_activity__opigno_h5p', $join1, 'h5p_points');
    $this->query->addRelationship('opigno_activity__field_opigno_quiz', $join2, 'opigno_activity__opigno_h5p');
    $this->query->addWhere('AND', 'opigno_activity__field_opigno_quiz.entity_id', '108', '=');
    $this->query->addWhereExpression('', "(h5p_points.points/h5p_points.max_points)*100 >= 70");

  }

}