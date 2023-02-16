<?php

namespace Drupal\atdove_opigno\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\opigno_module\Entity\OpignoActivity;

/**
 * A handler to provide a field that shows the status of a certificate.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("certificate_views_field")
 */
class CertificateViewsField extends FieldPluginBase {

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

    $percent_score = ($values->h5p_points_points / $values->h5p_points_max_points);
    $score = round($percent_score*100);

    if ($score >= 70) {
      $status = 'passed';
    }
    else {
      $status = 'failed';
    }

    return $status;
  }

}