<?php

namespace Drupal\atdove_organizations_subgroups\Routing;

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

    // Alter route for ggroup subgroup add/create controller
    // and provide our own class that extends the original.
    if ($route = $collection->get('entity.group_content.subgroup_add_form')) {
      $route->setDefault('_controller', '\Drupal\atdove_organizations_subgroups\Controller\SubgroupWizardController::addForm');
    }

    # Swap around access to the ground content add form.
    if ($route = $collection->get('entity.group_content.add_form')) {
      $route->setRequirement('_group_content_create_access', 'FALSE');
      $route->setRequirement('_custom_access', '\drupal\atdove_organizations_subgroups\Access\GroupContentAccessCheck::gcontentRouteCustomCreateAcccessCheck');
    }

    # Swap around the access to the group content delete form.
    if ($route = $collection->get('entity.group_content.delete_form')) {
      // Uncomment to debug $route values.
      // \Drupal::logger('atdove_organizations_subgroups')->notice(print_r($route->getRequirements(), TRUE));

      // Clear out all old requirements.
      $route->setRequirements([]);

      // Leave this as True. We want to make sure the group owns the piece of content for sanity's sake
      $route->setRequirement('_group_owns_content', 'TRUE');
      $route->setRequirement('_group_content_org_admin_access', 'TRUE');
    }

    # Allow org admins to add activites to training plans.
    if ($route = $collection->get('opigno_module.activities_bank_lpm.checked_activities')) {
      $route->setRequirement('_custom_access', '\Drupal\atdove_organizations\OrganizationsManager::currentUserIsOrgAdminInAnyGroup');
    }

    // Rewrite access to Drupal\entity_browser\Entity\EntityBrowser::route()
    // to allow an org admin add an image to a module.
    if ($route = $collection->get('entity_browser.media_entity_browser_groups')) {
      $route->setRequirements(['_custom_access' => '\Drupal\atdove_organizations\OrganizationsManager::currentUserIsOrgAdminInAnyGroup']);
    }
  }
}
