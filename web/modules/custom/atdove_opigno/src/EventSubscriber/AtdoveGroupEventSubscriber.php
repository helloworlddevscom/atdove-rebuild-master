<?php

namespace Drupal\atdove_opigno\EventSubscriber;

use Drupal\atdove_users\UsersManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\core_event_dispatcher\Event\Form\FormAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\core_event_dispatcher\Event\Entity\EntityAccessEvent;
use Drupal\core_event_dispatcher\Event\Entity\EntityInsertEvent;
use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\Group;
use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\Core\Url;

/**
 * Listens for access to learning paths. This is for the /group/{group}/edit pages.
 *
 * SEE: hook_event_dispatcher/examples/ExampleFormEventSubscribers.php .
 */
class AtdoveGroupEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   *
   * Establish what events this subscriber responds to.
   *
   * To add specific base form forms:
   * 'hook_event_dispatcher.form_base_node_form.alter' => 'alterNodeForm',
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::ENTITY_ACCESS => 'entityAccess',
      HookEventDispatcherInterface::ENTITY_INSERT => 'entityInsert',
    ];
  }

  /**
   * EQUIVALENT: hook_form_alter.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormAlterEvent $event
   *   The event.
   */
  public function entityAccess(EntityAccessEvent $event): void {
    $user = User::load(\Drupal::currentUser()->id());
    $roles = $user->getRoles();
    if (UsersManager::userHasPrivilegedRole()) {
      $event->addAccessResult(AccessResult::allowed());
      return;
    }
    $entity = $event->getEntity();
    $entity_type_id = $entity->getEntityTypeId();

    // If this is a learning path
    if (in_array($entity_type_id, ['group'], TRUE) && $entity->getGroupType()->id() == 'learning_path') {
      if (OrganizationsManager::currentUserIsOrgAdminInCurrentGroup()) {
        $event->addAccessResult(AccessResult::allowed());
      }
    }

    // If this is a module
    if (in_array($entity_type_id, ['opigno_module'], TRUE)) {
      if (OrganizationsManager::currentUserIsOrgAdminInCurrentGroup()) {
        $event->addAccessResult(AccessResult::allowed());
      }
    }

  }

/**
   * Entity Insert.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityInsertEvent $event
   *   The event.
   */
  public function entityInsert(EntityInsertEvent $event): void {
    $entity = $event->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $user = User::load(\Drupal::currentUser()->id());
    if (in_array($entity_type_id, ['group'], TRUE) && $entity->getGroupType()->id() == 'learning_path') {

      // Get user's group they are an og admin in
      $orgs = OrganizationsManager::getUserOrgs($user);

      foreach ($orgs as $org) {
        if (OrganizationsManager::isUserOrgAdmin($user, $org)) {
          $users_admin_group = $org;
          break;
        }
      }
      // Add learning path as a subgroup of user's group IF it's not a stock plan
      if (isset($users_admin_group) && $entity->field_stock->value != '1') {
        $group_org = \Drupal\group\Entity\Group::load($users_admin_group->id());
        $group_org->addContent($entity, 'subgroup:learning_path');
        $group_org->save();     
      }
    }
  }
}