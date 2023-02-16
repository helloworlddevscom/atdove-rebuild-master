<?php

namespace Drupal\atdove_organizations\Plugin\Menu\OrgAdmin;

use Drupal\Core\Menu\MenuLinkDefault;

/**
 * Default menu link for org_admin menu.
 * Gets group ID from current route and passes as parameter.
 */
class OrgAdminDefaultMenuLink extends MenuLinkDefault {

  /**
   * {@inheritDoc}
   */
  public function getRouteParameters() {
    // These are routes where we can retrieve the group ID.
    // In theory this menu should not appear on any other route.
    $gettable_routes = [
      'entity.group.canonical',
      'entity.group.edit_form',
      'view.group_members.page_1',
      'view.organization_members.page_1',
      'entity.group_content.add_form',
      'ginvite.invitation.bulk',
      'ginvite.invitation.bulk.confirm',
      'view.group_invitations.page_1',
      'view.subgroups.subgroups_page'
    ];
    // Get group ID and pass as parameter.
    $route = \Drupal::routeMatch()->getRouteName();
    if (in_array($route, $gettable_routes)) {
      return [
        'group' => \Drupal::routeMatch()->getRawParameter('group')
      ];
    }
    else {
      // If this menu is displayed on a different page we won't be
      // able to retrieve the group id. But we do need to return
      // something, so just return 1 I guess.
      return [
        'group' => 1
      ];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheMaxAge() {
    // Cache for one hour. 60 seconds * 60 minutes.
    return 60*60;
  }

  /**
   * {@inheritDoc}
   *
   * Purpose:
   * Cache for different users and routes.
   *
   * Link availability may change between users. Specifically manage billing.
   */
  public function getCacheContexts() {
    return [
      'user',
      'route',
    ];
  }
}
