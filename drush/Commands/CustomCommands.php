<?php
namespace Drush\Commands;

use drush\drush;

class CustomCommands extends DrushCommands {

  /**
   * To help better initialize the site, set the site UUID on config import.
   *
   * @hook pre-command config:import
   *
   */
  public function setUuid() {
    // Sets a hardcoded site uuid right before `drush config:import`
    $staticUuidIsSet = \Drupal::state()->get('static_uuid_is_set');
    $siteUUID = '7c82c2c4-3283-4d31-8bfb-836fee15f414';

    if (!$staticUuidIsSet) {
      $config_factory = \Drupal::configFactory();
      $config_factory->getEditable('system.site')->set('uuid', $siteUUID)->save();
      Drush::output()->writeln('Setting the correct UUID for this project: done.');
      \Drupal::state()->set('static_uuid_is_set', 1);
    }
  }
}
