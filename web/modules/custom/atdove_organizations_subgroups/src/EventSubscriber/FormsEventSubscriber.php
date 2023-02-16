<?php

namespace Drupal\atdove_organizations_subgroups\EventSubscriber;

use Drupal\atdove_users\UsersManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\core_event_dispatcher\Event\Form\FormAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Subscribe to form events (you can alter forms here) and alter them.
 *
 * SEE: hook_event_dispatcher/examples/ExampleFormEventSubscribers.php .
 */
class FormsEventSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

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
      HookEventDispatcherInterface::FORM_ALTER => 'alterForm',
    ];
  }

  /**
   * EQUIVALENT: hook_form_alter.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormAlterEvent $event
   *   The event.
   */
  public function alterForm(FormAlterEvent $event): void {
    $form_id = $event->getFormId();
    $form = &$event->getForm();

    switch ($form_id){
      // Create subgroup form.
      case "group_organizational_groups_ggroup-form_form":

        // Hide revision info.
        $form['revision_information']['#access'] = FALSE;

        break;
    }
  }
}
