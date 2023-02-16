<?php

namespace Drupal\atdove_opigno\EventSubscriber;

use Drupal\atdove_utilities\ValueFetcher;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Render\Element\Value;
use Drupal\core_event_dispatcher\Event\Entity\EntityAccessEvent;
use Drupal\workspaces\EntityAccess;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\core_event_dispatcher\Event\Entity\EntityInsertEvent;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\core_event_dispatcher\Event\Entity\EntityCreateEvent;
use Drupal\core_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Drupal\core_event_dispatcher\Event\Entity\EntityUpdateEvent;
use Drupal\group\Entity\GroupInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\redirect\Entity\Redirect;
use Drupal\opigno_module\Entity\OpignoActivity;
use Drupal\Core\Url;

/**
 * Class ProfileInsertEventSubscriber.
 *
 * Catch profile node events and react to them.
 *
 * @package Drupal\atdove_opigno\EventSubscriber
 */
class AssignmentNodeEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   * @return array
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::ENTITY_INSERT => 'entityInsert',
      HookEventDispatcherInterface::ENTITY_UPDATE => 'entityUpdate',
      HookEventDispatcherInterface::ENTITY_ACCESS => 'entityAccess',
    ];
  }

  /**
   * Entity insert.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityInsertEvent $event
   *   The event.
   */
  public function entityInsert(EntityInsertEvent $event): void {
    $entity = $event->getEntity();
    if ($entity instanceof NodeInterface && $entity->bundle() === 'assignment') {
      $uid = $entity->get('field_assignee')->getValue()[0]['target_id'];
      $assigned_content = $entity->get('field_assigned_content')->getValue()[0]['target_id'];
      $message = t('Your assignment "@name" has been changed.', ['@name' => $entity->label()]);
      $url = '/activity/' . $assigned_content;
      opigno_set_message($uid, $message, $url);
    }
  }
  /**
   * Entity update.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityUpdateEvent $event
   *   The event.
   */
  public function entityUpdate(EntityUpdateEvent $event): void {
    $entity = $event->getEntity();
    if ($entity instanceof NodeInterface && $entity->bundle() === 'assignment') {
      $uid = $entity->get('field_assignee')->getValue()[0]['target_id'];
      $assigned_content = $entity->get('field_assigned_content')->getValue()[0]['target_id'];
      $message = t('Your assignment "@name" has been changed.', ['@name' => $entity->label()]);
      $url = '/activity/' . $assigned_content;
      opigno_set_message($uid, $message, $url);
    }
  }

  /**
   * Entity access event.
   *
   * PURPOSE: Establish access to the node if user is assignee as well.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityAccessEvent $event
   */
  public function entityAccess(EntityAccessEvent $event) {
    $entity = $event->getEntity();

    if (
      $event->getOperation() == 'view'
      && $entity instanceof NodeInterface
      && $entity->bundle() === 'assignment'
    ) {
      $user = $event->getAccount();
      $assignee_uid = ValueFetcher::getFirstValue($entity, 'field_assignee');
      if ($user->id() == $assignee_uid) {
        $event->addAccessResult(AccessResult::allowed());
      }
    }
  }
}
