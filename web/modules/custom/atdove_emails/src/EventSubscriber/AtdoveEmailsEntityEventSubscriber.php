<?php

namespace Drupal\atdove_emails\EventSubscriber;

use Drupal\core_event_dispatcher\Event\Entity\EntityInsertEvent;
use Drupal\group\Entity\GroupInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Entity\EntityUpdateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\atdove_utilities\ValueFetcher;
use Drupal\atdove_emails\atDoveEmailFactory;

/**
 * Class AtdoveOrganizationsEntityEventSubscriber.
 *
 * Event subscriber looking for entity updates so as we can automate
 * enabling/disabling users of organizations based on organization status.
 */
class AtdoveEmailsEntityEventSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\atdove_emails\atDoveEmailFactory
   */
  private $atDoveEmailFactory;

  /**
   * AtdoveEmailsEntityEventSubscriber constructor.
   */
  public function __construct() {
    $this->atDoveEmailFactory = new atDoveEmailFactory();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::ENTITY_INSERT => 'entityInsert',
      HookEventDispatcherInterface::ENTITY_UPDATE => 'entityUpdate',
    ];
  }

  /**
   * Entity Create.
   *
   * JOB: If an assignment was created, email user.
   * JOB: If a trial is started, email user.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityInsertEvent $event
   *   The event.
   */
  public function entityInsert(EntityInsertEvent $event): void {
    $entity = $event->getEntity();

    $entityType = $entity->getEntityType()->id();
    $entityBundle = $entity->bundle();

    // Check if we're dealing with an assignment creation.
    if ($entityType == 'node' && $entityBundle == 'assignment') {
      $this->atDoveEmailFactory->emailUserAboutNewAssignment($entity);
    }

    // Check if we're dealing with a group creation.
    if (
      $entityType == 'group'
      && $entityBundle == 'organization'
    ) {
      $creator = $entity->getOwner();
      // Verify this is a group not created by an admin or superuser.
      if ($creator->id() > 1 && !$creator->hasRole('administrator')) {
        $this->atDoveEmailFactory->emailOrgAdminAboutNewTrial($entity);
      }
    }
  }

  /**
   * Entity update.
   *
   * JOB: If an assignment/quiz was completed, email org admin.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityUpdateEvent $event
   *   The event.
   */
  public function entityUpdate(EntityUpdateEvent $event): void {
    $entity = $event->getEntity();

    $entityType = $entity->getEntityType()->id();
    $entityBundle = $entity->bundle();

    if ($entityType == 'node' && $entityBundle == 'assignment') {
      $entityOriginal = $event->getOriginalEntity();
      if (
        ValueFetcher::getFirstValue($entityOriginal, 'field_assignment_status') != 'passed'
        && ValueFetcher::getFirstValue($entity, 'field_assignment_status') == 'passed'
        && !ValueFetcher::isEmpty($entity, 'field_organization')
      ) {
        $this->atDoveEmailFactory->emailOrgAdminAssignentComplete($entity);
      }
    }
  }

}
