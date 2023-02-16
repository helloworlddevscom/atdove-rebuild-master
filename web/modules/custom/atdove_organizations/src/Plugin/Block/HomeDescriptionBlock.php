<?php

namespace Drupal\atdove_organizations\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a description block meant to be
 * placed on the organization group home/canonical route.
 *
 * @Block(
 *   id = "atdove_organizations_home_description",
 *   admin_label = @Translation("Organization Home Description"),
 *   category = @Translation("atdove")
 * )
 */
class HomeDescriptionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#theme' => 'block__atdove_organizations_home_description',
    ];
    return $build;
  }

}
