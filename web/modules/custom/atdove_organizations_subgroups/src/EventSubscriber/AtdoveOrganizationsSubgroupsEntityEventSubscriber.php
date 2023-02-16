<?php

namespace Drupal\atdove_organizations\EventSubscriber;

use Drupal\core_event_dispatcher\Event\Entity\EntityCreateEvent;
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
      HookEventDispatcherInterface::ENTITY_CREATE => 'syncUsersWithGroup',
      HookEventDispatcherInterface::ENTITY_UPDATE => 'syncUsersWithGroup',
    ];
  }

  /**
   * Entity update.
   *
   * @param $event
   *   The event.
   */
  public function syncUsersWithGroup($event): void {
    $entity = $event->getEntity();

    // Anytime an org admin is added to a group, add to subgroups.
    if (
      $event instanceof EntityUpdateEvent
    ) {

    }

    // Anytime a sub group is created, add all org admins as
    if (
      $event instanceof EntityCreateEvent
      && $entity instanceof GroupInterface
      && $entity->getGroupType()->id() == 'organizational_groups'
    ) {

    }
  }

}
