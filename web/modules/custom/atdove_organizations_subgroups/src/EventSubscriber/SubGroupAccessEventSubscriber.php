<?php

namespace Drupal\atdove_organizations_subgroups\EventSubscriber;

use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\Core\Access\AccessResult;
use Drupal\core_event_dispatcher\Event\Entity\EntityAccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\user\Entity\User;

/**
 * Class ProfileInsertEventSubscriber.
 *
 * Catch profile node events and react to them.
 *
 * @package Drupal\atdove_opigno\EventSubscriber
 */
class SubGroupAccessEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   * @return array
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::ENTITY_ACCESS => 'entityAccess',
    ];
  }

  /**
   * Entity access event.
   *
   * PURPOSE: Org admins of parent group can do anything with a sub group.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityAccessEvent $event
   */
  public function entityAccess(EntityAccessEvent $event) {
    $entity = $event->getEntity();

    // Allow access to sub groups AS GROUP CONTENT for Org Admins of parent group.
    if (
      $entity->getEntityTypeId() === 'group_content'
      && $entity->bundle() == 'group_content_type_c20cc86eb7dd2'
    ) {
      $group = $entity->getGroup();

      $member = $group->getMember($event->getAccount());
      if (
        $member
        && OrganizationsManager::isUserOrgAdmin($member->getUser(), $group)
      ) {
        $event->addAccessResult(AccessResult::allowed());
      }
    }

    // Allow access to sub groups for Org Admins of parent group.
    if (
      $entity->getEntityTypeId() === 'group'
      && $entity->bundle() === 'organizational_groups'
      && OrganizationsManager::isUserOrgAdminOfParentGroup(
        User::load($event->getAccount()->id()),
        $entity
      )
    ) {
      $event->addAccessResult(AccessResult::allowed());
    }
  }
}
