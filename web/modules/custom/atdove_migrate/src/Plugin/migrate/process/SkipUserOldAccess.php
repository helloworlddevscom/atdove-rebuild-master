<?php

namespace Drupal\atdove_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\Core\Datetime\DrupalDateTime;

 /**
  * @MigrateProcessPlugin(
  *   id = "skip_if_user_old_access"
  * )
  */
class SkipUserOldAccess extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateException
   *   If invalid parameters or values are used or a mandatory parameter is
   *   missing.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $created = $value;
    $access = $row->getSourceProperty('access'); 

    $current_timestamp = new DrupalDateTime();
    $current_timestamp->getTimestamp();
    $current_timestamp->modify('-1 year');
    $timestamp = strtotime($current_timestamp);
    // Skip users that have never accessed the site and are older than 1 year.
    if (($created <= $timestamp) && ($access == 0)) {
      $message = isset($this->configuration['message']) ? $this->configuration['message'] : '';
      throw new MigrateSkipRowException($message);
    }
    return $value;
  }
}