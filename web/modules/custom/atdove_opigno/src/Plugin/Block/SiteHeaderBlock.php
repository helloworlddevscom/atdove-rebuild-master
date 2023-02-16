<?php

namespace Drupal\atdove_opigno\Plugin\Block;

use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\atdove_users\UsersManager;
use Drupal\opigno_dashboard\Plugin\Block\SiteHeaderBlock as BaseSiteHeaderBlock;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * The site header block.
 * Extends the default Opigno site header block from opigno_dashboard module
 * so that we can customize it for AtDove.
 *
 * @Block(
 *  id = "atdove_opigno_site_header_block",
 *  admin_label = @Translation("AtDove Opigno Site header block"),
 *  category = @Translation("Opigno"),
 * )
 *
 * @package Drupal\opigno_dashboard\Plugin\Block
 */
class SiteHeaderBlock extends BaseSiteHeaderBlock {

  /**
   * Prepare the user dropdown menu.
   * Customized to add invitations link and remove unnecessary links.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $role
   *   The user role.
   *
   * @return array
   *   The array to build the user dropdown menu.
   */
  protected function buildUserDropdownMenu(TranslatableMarkup $role): array {
    if (!$this->user instanceof UserInterface) {
      return [];
    }

    $links = [];

    // If user is an org admin, create links to orgs user belongs to.
    $orgs = [];
    $tempstore = \Drupal::service('tempstore.private');
    $store = $tempstore->get('atdove_organizations');
    if (!empty($store)) {
      $orgs = $store->get('user_orgs');
    }

    if (empty($orgs)) {
      // If user belongs to more than two orgs, tough luck.
      // There's only so much room in the menu.
      // This also avoids increasing load times.
      $orgs = OrganizationsManager::getUserOrgs($this->user, 2);

      if (!empty($orgs)) {
        $store->set('user_orgs', $orgs);
      }
    }

    $links['my-orgs'] = [
      'title' => $this->t('My Orgs'),
      'path' => Url::fromRoute('view.my_orgs.page_1', ['user' => $this->user->id()])->toString(),
      'icon_class' => 'fi-rr-info',
    ];

    foreach ($orgs as $org_key => $org) {
      if (OrganizationsManager::isUserOrgAdmin($this->user, $org) || UsersManager::userHasPrivilegedRole($this->user)) {
        $links['organization_' . $org_key] = [
          'title' => $org->label(),
          'path' => Url::fromRoute('entity.group.canonical', ['group' => $org->id()])->toString(),
          'icon_class' => 'fi-rr-users',
        ];
      }
    }

    $links['achievements'] = [
      'title' => $this->t('Achievements'),
      'path' => Url::fromRoute('opigno_statistics.user_achievements_page', ['user' => $this->user->id()])->toString(),
      'icon_class' => 'fi-rr-trophy',
    ];

    if (UsersManager::userHasInvitations()) {
      $links['invitations'] = [
        'title' => $this->t('Invitations'),
        'path' => Url::fromRoute('view.my_organization_invitations.page_1', ['user' => $this->user->id()])->toString(),
        'icon_class' => 'fi-rr-envelope',
      ];
    }

    $links['logout'] = [
      'title' => $this->t('Logout'),
      'path' => Url::fromRoute('user.logout')->toString(),
      'icon_class' => 'fi-rr-sign-out',
    ];

    return [
      'name' => Link::createFromRoute($this->user->getDisplayName(), 'entity.user.canonical', ['user' => (int) $this->user->id()]),
      'role' => $role,
      'is_admin' => $this->user->hasPermission('access administration pages'),
      'links' => $links,
    ];
  }
}
