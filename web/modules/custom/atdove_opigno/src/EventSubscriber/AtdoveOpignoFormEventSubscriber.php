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

/**
 * Subscribe to form events (you can alter forms here) and alter them.
 *
 * SEE: hook_event_dispatcher/examples/ExampleFormEventSubscribers.php .
 */
class AtdoveOpignoFormEventSubscriber implements EventSubscriberInterface {
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
      case "atdove_opigno_assign_to_person":
        // Disable access to some fields.
        $this->RestrictAssignmentToJustUsersOfOrgs($form);
        break;

      case "group_learning_path_edit_form":
        $has_priviledged_roles = UsersManager::userHasPrivilegedRole(\Drupal::currentUser());
        if (!$has_priviledged_roles) {
          $form['field_stock']['#access'] = FALSE;
        }
        break;

      case "group_learning_path_add_form":
        $has_priviledged_roles = UsersManager::userHasPrivilegedRole(\Drupal::currentUser());
        if (!$has_priviledged_roles) {
          $form['field_stock']['#access'] = FALSE;
        }
        break;
    }
  }

  /**
   * Restrict access to the stripe field on organizations.
   */
  private function RestrictAssignmentToJustUsersOfOrgs(&$form) {
    $form['add_users']['#selection_handler'] = 'atdove:users_of_current_user_groups';
    $form['add_users']['#cache'] = [
      'contexts' => ['user'],
    ];
    $form['#cache']['contexts'][] = 'user';
  }

}
