<?php

namespace Drupal\atdove_organizations_subgroups\Plugin\views\access;

use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\atdove_users\UsersManager;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupPermissionHandlerInterface;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides group permission-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "org_admin_in_group_or_parent_group",
 *   title = @Translation("User is Org Admin in Group Or Parent Group"),
 *   help = @Translation("Access will be granted to users who are an org admin in the group or a parent group, as determined by group param.")
 * )
 */
class OrgAdminInGroupOrParentGroup extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = FALSE;

  /**
   * The group permission handler.
   *
   * @var \Drupal\group\Access\GroupPermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The group entity from the route.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The group context from the route.
   *
   * @var \Drupal\Core\Plugin\Context\ContextInterface
   */
  protected $context;

  /**
   * Constructs a Permission object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\group\Access\GroupPermissionHandlerInterface $permission_handler
   *   The group permission handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The group route context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GroupPermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler, ContextProviderInterface $context_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->permissionHandler = $permission_handler;
    $this->moduleHandler = $module_handler;

    $contexts = $context_provider->getRuntimeContexts(['group']);
    $this->context = $contexts['group'];
    $this->group = $this->context->getContextValue();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('group.permissions'),
      $container->get('module_handler'),
      $container->get('group.group_route_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $user = User::load($account->id());

    // Group admins can access members.
    if (
      $this->group->getGroupType()->id() == 'organization'
      && (
        UsersManager::userHasPrivilegedRole($user)
        || OrganizationsManager::isUserOrgAdmin($user, $this->group)
      )
    ) {
      return TRUE;
    }

    if (
      $this->group->getGroupType()->id() == 'organizational_groups'
      && (
        UsersManager::userHasPrivilegedRole($user)
        || OrganizationsManager::isUserOrgAdminOfParentGroup($user, $this->group)
      )
    ) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_permission', 'access content');

    // Upcast any %group path key the user may have configured so the
    // '_group_permission' access check will receive a properly loaded group.
    $route->setOption('parameters', ['group' => ['type' => 'entity:group']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::mergeMaxAges(Cache::PERMANENT, $this->context->getCacheMaxAge());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(['user'], $this->context->getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->context->getCacheTags();
  }

}
