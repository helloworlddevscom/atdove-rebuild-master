<?php

namespace Drupal\atdove_billing\Plugin\Block;

use Drupal\atdove_users\UsersManager;
use Drupal\block\Entity\Block;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Block\BlockBase;
use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;

/**
 * Atdove Billing Manage Billing Block to display on Orgs.
 *
 * @Block(
 *  id = "atdove_billing_manage_billing_block",
 *  admin_label = @Translation("AtDove Billing Manage Billing Block"),
 *  category = @Translation("atdove"),
 * )
 *
 * @package Drupal\opigno_dashboard\Plugin\Block
 */
class OrgManageBillingBlock extends BlockBase {

  /**
   * @var \Drupal\group\Entity\Group;
   */
  private $group;

  /**
   * OrgManageBillingBlock constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // @todo Convert to DI.
    $this->group = \Drupal::routeMatch()->getParameter('group');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user = user::load(\Drupal::currentUser()->id());
    $group = \Drupal::routeMatch()->getParameter('group');

    $url = Url::fromRoute('atdove_billing.stripe_customer_portal.forward', array('group' => $group->id()));
    $billing_link = Link::fromTextAndUrl(t('Manage Billing'), $url);
    $billing_link = $billing_link->toRenderable();
    $billing_link['#attributes'] = array('class' => array('button', 'button--primary'));

    return $billing_link;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(),
      ['group:' . $this->group->id()]
    );
  }

  /**
   * Set the cache maximum time to X hours. (60s * 60m * Xh)
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 60*60*72;
    return 1;
  }

  /**
   * Access checking for the block beyond default block settings.
   *
   * Purpose: Check if user is a group member if they don't have a priveleged role.
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = $this->blockAccess($account);

    // If all other access checks (group) check out, and the user isn't priveleged, check for group role.
    if (
      $access instanceof AccessResultAllowed
       && !UsersManager::userHasPrivilegedRole($account)
    ) {
      $group = \Drupal::routeMatch()->getParameter('group');
      $member = $group->getMember($account);

      // If user is not an org admin.
      if ($member && !in_array('organization-admin', array_keys($member->getRoles()))) {
        return AccessResult::forbidden('User is not an org admin');
      }
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

}
