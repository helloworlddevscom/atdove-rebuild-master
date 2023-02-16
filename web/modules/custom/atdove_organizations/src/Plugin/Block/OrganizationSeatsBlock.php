<?php

namespace Drupal\atdove_organizations\Plugin\Block;

use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\atdove_users\UsersManager;
use Drupal\atdove_utilities\ValueFetcher;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\user\Entity\User;

/**
 * Provides a description block meant to be
 * placed on the organization group home/canonical route.
 *
 * @Block(
 *   id = "atdove_organizations_seats",
 *   admin_label = @Translation("Organization Seats"),
 *   category = @Translation("atdove")
 * )
 */
class OrganizationSeatsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $route_match = \Drupal::routeMatch();
    /** @var \Drupal\group\Entity\Group $group */
    $group = $route_match->getParameter('group');
    $memberCount = count($group->getMembers());
    $maxMembers = ValueFetcher::getFirstValue($group, 'field_member_limit');

    $build['content'][] = [
      '#markup' =>
        $this->t(
          'Your organization has @current of @max members',
          [
            '@current' => $memberCount,
            '@max' => $maxMembers,
          ]
        )
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * PURPOSE: Restrict block access for user roles.
   */
  public function blockAccess(AccountInterface $account) {

    $route_match = \Drupal::routeMatch();
    /** @var \Drupal\group\Entity\Group $group */
    $group = $route_match->getParameter('group');

    // Make sure we're dealing with a group, and that it's an organization.
    if (
      is_null($group)
      || $group->getGroupType()->id() !== 'organization'
    ) {
      return AccessResult::forbidden('No group, or group not organization');
    }

    // Let priveleged users through!
    if (UsersManager::userHasPrivilegedRole($account)) {
      return AccessResult::allowed();
    }

    // Check if the user is not a current member or an org admin for the group.
    if (
      !$group->getMember($account)
      || !OrganizationsManager::isUserOrgAdmin(
        User::load($account->id()),
        $group
      )
    ) {
      return AccessResult::forbidden('User is not a group member or an org admin.');
    }

    return AccessResult::allowed();
  }

  /**
   * Add a cache tag for each group for the block cache tags when rendering.
   */
  public function getCacheTags() {
    if ($group = \Drupal::routeMatch()->getParameter('group')) {
      return Cache::mergeTags(parent::getCacheTags(), array('node:' . $group->id()));
    } else {
      return parent::getCacheTags();
    }
  }

  /**
   * Add route cache context to the block.
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }

}
