<?php

namespace Drupal\atdove_opigno\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\opigno_module\Entity\OpignoActivity;

/**
 * A handler to provide a field that shows the related content Quiz Title.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("h5p_quiz_title_views_field")
 */
class H5pQuizTitleViewsField extends FieldPluginBase {

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
    $quiz_id = $values->id;

    $quiz_title = $connection->select('h5p_content', 'opfd')
      ->fields('opfd', ['title'])
      ->condition('id', $quiz_id)
      ->execute()
      ->fetchField();

   // $link = '<a href="/activity/' . $quiz_id . '">' . $quiz_title . '</a>';
    $link = $quiz_title;
    return $link;
  }

}