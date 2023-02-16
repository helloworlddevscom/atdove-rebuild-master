<?php

namespace Drupal\atdove_organizations_subgroups\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines dynamic local actions for deleting subgroups.
 */
class DeleteSubgroupLocalAction extends LocalActionDefault {

  /**
   * {@inheritDoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    // Get group ID and pass as parameter.
    return [
      'group' => $route_match->getRawParameter('group'),
    ];
  }
}
