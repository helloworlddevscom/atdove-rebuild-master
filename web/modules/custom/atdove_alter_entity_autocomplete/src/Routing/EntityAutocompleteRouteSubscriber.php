<?php

namespace Drupal\atdove_alter_entity_autocomplete\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class EntityAutocompleteRouteSubscriber extends RouteSubscriberBase {

  /**
   * @param RouteCollection $collection
   * @return void
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('system.entity_autocomplete')) {
      $route->setDefault('_controller', '\Drupal\atdove_alter_entity_autocomplete\Controller\EntityAutocompleteController::handleAutocomplete');
    }
  }

}
