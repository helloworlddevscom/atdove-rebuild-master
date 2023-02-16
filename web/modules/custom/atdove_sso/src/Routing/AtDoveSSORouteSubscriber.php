<?php

namespace Drupal\atdove_sso\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * AtDove SSO route subscriber.
 */
class AtDoveSSORouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Alter route for core login to custom controller that displays
    // AtDove SSO Login block that displays core login and openid_connect login forms.
    if ($route = $collection->get('user.login')) {
      $route->setDefault('_controller', '\Drupal\atdove_sso\Controller\AtDoveSSOLoginFormController::content');
      $route->setDefault('_title', 'Scrub In');
    }
  }
}
