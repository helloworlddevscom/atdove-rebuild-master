<?php

namespace Drupal\atdove_organizations_invite\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Alter route for ginvite bulk invite confirm form
    // and provide our own form class that extends the original.
    if ($route = $collection->get('ginvite.invitation.bulk.confirm')) {
      $route->setDefault('_form', '\Drupal\atdove_organizations_invite\Form\BulkGroupInvitationConfirm');
    }

    // Alter route for ginvite accept/decline invitations controller
    // and provide our own controller class that extends the original.
    if ($route = $collection->get('ginvite.invitation.accept')) {
      $route->setDefault('_controller', '\Drupal\atdove_organizations_invite\Controller\InvitationOperations::accept');
    }

    // Alter route for ginvite accept/decline invitations controller
    // and provide our own controller class that extends the original.
    if ($route = $collection->get('ginvite.invitation.bulk')) {
       $route->setRequirement('_custom_access', '\Drupal\atdove_organizations\OrganizationsManager::groupHasMembershipLicensesLeft');
    }

    // Alter route for the "invitations" view.
    // Purpose: Add check for a group role in any group to view invites.
    if ($route = $collection->get('view.my_organization_invitations.page_1')) {
      $route->setRequirement('_custom_access', '\Drupal\atdove_organizations\OrganizationsManager::currentUserIsOrgAdminInAnyGroup');
    }

    // Alter route for the "group members" view.
    // Purpose: Add check for an org admin to view members in learning paths in their group.
    if ($route = $collection->get('view.group_members.page_1')) {
      $route->setRequirement('_custom_access', '\Drupal\atdove_organizations\OrganizationsManager::currentUserIsOrgAdminInCurrentGroup');
    }
  }
}
