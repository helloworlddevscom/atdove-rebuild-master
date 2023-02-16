<?php

namespace Drupal\atdove_opigno\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\opigno_module\Entity\OpignoActivity;

/**
 * A handler to provide a field that shows the related content to a quiz.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("h5p_related_content_views_field")
 */
class H5pRelatedContentViewsField extends FieldPluginBase {

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

    $connection = \Drupal::database();
    // H5P ID of quiz they took, this is NOT the opigno quiz activity ID!
    $quiz_id = $values->id;
    // In the migration, the h5p content id was set equal to the activity id. 
    // But in creating new content that is not neccesarily the case.
    $related_activity_id = $connection->select('opigno_activity__opigno_h5p', 'ophp')
      ->fields('ophp', ['entity_id'])
      ->condition('opigno_h5p_h5p_content_id', $quiz_id)
      ->execute()
      ->fetchField();

    $related_activity = $connection->select('opigno_activity__field_opigno_quiz', 'opq')
      ->fields('opq', ['entity_id'])
      ->condition('field_opigno_quiz_target_id', $related_activity_id)
      ->execute()
      ->fetchField();

    $opigno_activity = OpignoActivity::load($related_activity);
    if ($opigno_activity) {
      $name = $opigno_activity->name->getValue()[0]['value'];
      $id = $opigno_activity->id->getValue()[0]['value'];
    }
    $link = '<a href="/activity/' . $id . '">' . $name . '</a>';

    if (isset($id)) {
      return $link;
    }
    else {
      return NULL; 
    }
  }
}