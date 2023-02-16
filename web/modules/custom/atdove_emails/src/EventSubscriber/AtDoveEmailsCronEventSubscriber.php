<?php

namespace Drupal\atdove_emails\EventSubscriber;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\State;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\group\Entity\Group;

/**
 * Cron event subscriber.
 */
class AtDoveEmailsCronEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The state system.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;


  /**
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $dateTime;

  /**
   * The interval with which we wish to have this cron job run at MAXMIMUM.
   *
   * @var int
   */
  protected $interval = 60*60*24;



  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, State $state) {
    $this->entityTypeManager = $entityTypeManager;
    $this->state = $state;
    $this->dateTime = new DrupalDateTime('now');
  }

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
      HookEventDispatcherInterface::CRON => 'informTrialUsersOfExpiration',
    ];
  }

  /**
   * EQUIVALENT: hook_cron().
   *
   * Find all orgs that are expired without a stripe ID and set their status
   * to inactive.
   *
   * NOTE: Interval is critical as we can't schedule this with ultimate cron.
   */
  public function informTrialUsersOfExpiration(): void {
//    $last_run = $this->state->get('AtDoveOrgLegacyExpirationLastRun');
//
//    if (
//      is_null($last_run)
//      || ($last_run + $this->interval < time())
//    ) {
//      $current_time = $this->dateTime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
//
//      // Load all expired groups
//      $expired_groups = $this->entityTypeManager->getStorage('group')->getQuery()
//        ->condition('field_stripe_customer_id', NULL, 'IS NULL')
//        ->condition('field_current_expiration_date', $current_time, '<')
//        ->condition('field_license_status', 'active')
//        ->accessCheck(FALSE)
//        ->execute()
//        ;
//
//      // Set all of the active and expired groups to inactive.
//      foreach ($expired_groups as $expired_group) {
//        $group = Group::load($expired_group);
//        $group->field_license_status->value = 'inactive';
//        $group->save();
//      }
//
//      $this->state->set('AtDoveOrgLegacyExpirationLastRun', time());
//    }

  }

}
