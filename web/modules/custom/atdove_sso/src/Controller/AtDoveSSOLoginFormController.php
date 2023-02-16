<?php
/**
 * @file
 * Contains \Drupal\atdove_sso\Controller\AtDoveSSOLoginFormController.
 */

namespace Drupal\atdove_sso\Controller;

use Drupal\block\Entity\Block;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class AtDoveSSOLoginFormController.
 *
 * Controller to render custom AtDove SSO Login block
 * containing both core login and openid_connect forms.
 *
 * Based on: https://www.drupal.org/docs/drupal-apis/routing-system/introductory-drupal-8-routes-and-controllers-example
 */
class AtDoveSSOLoginFormController extends ControllerBase {

  /**
   * Returns a render-able array for a page.
   *
   * @return array
   */
  public function content()
  {
    // Return the markup of our custom block.
    // We use a block instead of our own template for the
    // controller because if the login form needs
    // to be rendered elsewhere it is easily placeable as a block.
    $block = Block::load('atdove_sso_login');
    $build = \Drupal::entityTypeManager()->getViewBuilder('block')->view($block);
    $build = render($build);

    return [
      '#markup' => $build
    ];
  }
}
