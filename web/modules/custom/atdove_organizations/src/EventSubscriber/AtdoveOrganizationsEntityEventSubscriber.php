<?php

namespace Drupal\atdove_organizations\EventSubscriber;

use Drupal\group\Entity\GroupInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Entity\EntityDeleteEvent;
use Drupal\core_event_dispatcher\Event\Entity\EntityUpdateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\atdove_utilities\ValueFetcher;
use Drupal\atdove_organizations\OrganizationsManager;

/**
 * Class AtdoveOrganizationsEntityEventSubscriber.
 *
 * Event subscriber looking for entity updates so as we can automate
 * enabling/disabling users of organizations based on organization status.
 */
class AtdoveOrganizationsEntityEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   *
   * NOTE: Entity delete removed until we can determine for sure we need it.
   *
   * HookEventDispatcherInterface::ENTITY_DELETE => 'entityDelete',
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::ENTITY_UPDATE => 'entityUpdate',
    ];
  }

  /**
   * Entity update.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityUpdateEvent $event
   *   The event.
   */
  public function entityUpdate(EntityUpdateEvent $event): void {
    $entity = $event->getEntity();

    // Only do this for entities of type Org.
    if (
      $entity instanceof GroupInterface
      && $entity->getGroupType()->id() == 'organization'
    ) {
      $group = $entity;
      $group_original = $event->getOriginalEntity();

      // Check if a groups field status has changed.
      if (
        !ValueFetcher::areEqual(
          $group,
          $group_original,
          'field_license_status'
        )
      ) {
        $status = ValueFetcher::getFirstValue($group, 'field_license_status');

        switch ($status) {
          case 'active':
            $this->addRolesForOrgMembers($entity);
            break;
          case 'inactive':
            $this->removeRolesFromOrgMembers($entity);
            break;
          default:
            \Drupal::logger('atdove_organizations')->error("AtdoveOrganizationsEntityEventSubscriber.php::entityUpdate found no value for status. Value; $status");
            break;
        }
      }
    }

  }

  /**
   * Entity delete.
   *
   * On entity delete remove role for all users of the group
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityDeleteEvent $event
   *   The event.
   */
  public function entityDelete(EntityDeleteEvent $event): void {
    $entity = $event->getEntity();

    // Only do this for entities of type Org.
    if (
      $entity instanceof GroupInterface
      && $entity->getGroupType()->id() == 'organization'
    ) {
      $this->removeRolesFromOrgMembers($entity);
    }
  }

  /**
   * Adds the active org member role to all users in an activated group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Fully loaded $group org.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addRolesForOrgMembers(GroupInterface $group) {
    $members = $group->getMembers();

    // Add active org role to all members of a group that is moved to active.
    foreach ($members as $member) {
      $user = $member->getUser();
      if (!$user->hasRole('active_org_member')) {
        $user->addRole('active_org_member');
        $user->save();
      }
    }
  }

  /**
   * Removes the role active_org_member when a group is deactivated.
   *
   * Will not remove the role if user is a member of another org that is active.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeRolesFromOrgMembers(GroupInterface $group) {
    $members = $group->getMembers();

    // Remove Active org role from users with no active organizations.
    foreach ($members as $member) {
      $user = $member->getUser();
      if (!OrganizationsManager::userHasAnActiveOrg($user)) {
        $user->removeRole('active_org_member');
        $user->save();
      }
    }
  }

}
