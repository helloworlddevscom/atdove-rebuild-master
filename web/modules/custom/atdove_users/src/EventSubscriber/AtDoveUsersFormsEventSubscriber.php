<?php

namespace Drupal\atdove_users\EventSubscriber;

use Drupal\atdove_users\UsersManager;
use Drupal\Core\Render\Markup;
use Drupal\core_event_dispatcher\Event\Form\FormAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\user\Entity\User;

/**
 * Subscribe to form events (you can alter forms here) and alter them.
 *
 * SEE: hook_event_dispatcher/examples/ExampleFormEventSubscribers.php .
 */
class AtDoveUsersFormsEventSubscriber implements EventSubscriberInterface {

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
    $form_state = $event->getFormState();

    switch ($form_id){
      // User login form.
      case 'user_login_form':
        // Change label of username field to email.
        $form['name']['#title'] = t('Email address');
        $form["actions"]["submit"]["#value"] = Markup::create(t('Scrub In'));

        break;

      // User edit and register forms.
      case 'user_form':
      case 'user_register_form':
        // Hide select fields for non privileged users. Notate privileged fields.
        if (!UsersManager::userHasPrivilegedRole(\Drupal::currentUser())) {
          $form['account']['name']['#access'] = FALSE;
          $form['account']['roles']['#access'] = FALSE;
        }
        else {
          $form['account']['name']['#disabled'] = TRUE;
          $form['account']['name']['#description'] = t('This field is only visible to privileged users. It is automatically set equal to Email address when this form is submitted.');
          $form['account']['status']['#description'] = t('This field is only visible to privileged users.');
        }

        // Set email field to required. Not sure why we are unable
        // to do this through the admin GUI.
        $form['account']['mail']['#required'] = TRUE;

        // Add custom submit validation to set username equal to email.
        // We need ours to run before the core validation is run,
        // because the username field will not have a value yet, causing
        // the core validation to fail.
        if (!isset($form['actions']['submit']['#validate'])) {
          $form['actions']['submit']['#validate'] = [];
        }
        array_unshift($form['actions']['submit']['#validate'], '\Drupal\atdove_users\EventSubscriber\AtDoveUsersFormsEventSubscriber::userFormValidate');

        break;

      // User password reset form.
      case 'user_pass':
        $form['name']['#title'] = t('Email address');

        break;
    }
  }

  /**
   * Custom validation handler for user edit and register forms.
   *
   * Set username equal to email on submit.
   */
  public static function userFormValidate(&$form, $form_state) {
    $form_state->setValue('name', $form_state->getValue('mail'));
    // Avoid "entity validation was skipped" error.
    // See: https://www.drupal.org/project/entity/issues/2833337
    $form_state->setTemporaryValue('entity_validated', TRUE);
  }
}
