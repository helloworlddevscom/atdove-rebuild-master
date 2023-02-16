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
 * Filters by the Content Category Taxonomy Vocabulary.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("con_cat_groups_views_filter")
 */
class ContentCategoryViewsFilter extends ManyToOne {

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

    $this->valueTitle = t('Filter by Content Category');
    $this->definition['options callback'] = [$this, 'generateOptions'];
    $this->currentDisplay = $view->current_display;
  }

/**
   * Helper function that generates the options.
   * @return array
   */
  public function generateOptions() {
    $n_terms =[];
    $vid = 'content_categories';
    $terms =\Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vid, 0, null, FALSE);

    foreach ($terms as $key => $value) {
      $n_terms[$value->tid] = $value->name;
    }
    return $n_terms;
  }

  /**
   * Helper function that builds the query.
   */
  public function query() {

    if (!empty($this->value) && (!empty(reset($this->value)))) {

      $configuration1 = [
        'table' => 'opigno_activity__field_opigno_quiz',
        'field' => 'field_opigno_quiz_target_id',
        'left_table' => 'h5p_points',
        'left_field' => 'content_id',
        'operator' => '=',
      ];
      $configuration2 = [
        'table' => 'opigno_activity__field_content_category',
        'field' => 'entity_id',
        'left_table' => 'opigno_activity__field_opigno_quiz',
        'left_field' => 'entity_id',
        'operator' => '=',
      ];

      $join1 = Views::pluginManager('join')->createInstance('standard', $configuration1);
      $join2 = Views::pluginManager('join')->createInstance('standard', $configuration2);

      $this->query->addRelationship('opigno_activity__field_opigno_quiz', $join1, 'h5p_points');
      $this->query->addRelationship('opigno_activity__field_content_category', $join2, 'opigno_activity__field_opigno_quiz');
      // Content category selected value
      $this->query->addWhere('AND', 'opigno_activity__field_content_category.field_content_category_target_id', $this->value, 'IN');
    }
    else {
       parent::query();
    }
  }
}