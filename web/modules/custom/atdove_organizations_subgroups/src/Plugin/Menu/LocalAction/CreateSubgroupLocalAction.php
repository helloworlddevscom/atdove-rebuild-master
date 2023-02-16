<?php

namespace Drupal\atdove_organizations_subgroups\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines dynamic local actions for creating subgroups.
 */
class CreateSubgroupLocalAction extends LocalActionDefault {

  /**
   * {@inheritDoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    // Get group ID and pass as parameter.
    return [
      'group' => $route_match->getRawParameter('group'),
      'group_type' => 'organizational_groups'
    ];
  }
}
