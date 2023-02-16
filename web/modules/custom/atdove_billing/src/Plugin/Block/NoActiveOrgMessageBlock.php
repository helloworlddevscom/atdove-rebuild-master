<?php

namespace Drupal\atdove_billing\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\user\Entity\User;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;

/**
 * No Active Organizations Message Block
 *
 * Relies on:
 * https://www.drupal.org/project/drupal/issues/2986958
 *
 * You can choose to display this black for users who lack role. In our case
 * users who lack active_org_member
 *
 * @Block(
 *  id = "atdove_billing_no_active_org_message_block",
 *  admin_label = @Translation("AtDove Billing No Active Org Message Block"),
 *  category = @Translation("atdove"),
 * )
 *
 * @package Drupal\opigno_dashboard\Plugin\Block
 */
class NoActiveOrgMessageBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user = user::load(\Drupal::currentUser()->id());
    $user_orgs = OrganizationsManager::getUserInactiveOrgs($user);
    $expired_org_links = [];

    // Exit with no content to render if the user has no orgs.
    if (empty($user_orgs)) {
      return [];
    }

    // Iterative over all of the users groups and determine the output.
    foreach ($user_orgs as $user_org) {
      $membership = $user_org->getMember($user);
      $org_name = $user_org->label();

      if (in_array('organization-admin', array_keys($membership->getRoles()))) {
        $expired_org_links[] =
          $this->t("The subscription for your organization @orgname has expired. Please renew your subscription @link.", [
            '@orgname' => $org_name,
            '@link' => link::fromTextAndUrl($this->t('here'), Url::fromUserInput('/group/' .$user_org->id() . '/manage-billing'))->toString(),
          ]);
      }
      else {
        $expired_org_links[] = "Your Organization $org_name has had their subscription expire. Please reach out to your organizations billing admins to have them renew.";
      }
    }

    foreach($expired_org_links as $expired_org_link){
      \Drupal::messenger()->addMessage($expired_org_link, \Drupal::messenger()::TYPE_ERROR);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(),
      ['user:' . \Drupal::currentUser()->id()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    //if you depends on \Drupal::routeMatch()
    //you must set context of this block with 'route' context tag.
    //Every new route this block will rebuild
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

  /**
   * Set the cache maximum time to 12 hours. (60s * 60m * 12h)
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 60*60*12;
  }

}
