<?php

namespace Drupal\atdove_sso\EventSubscriber;

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
class AtDoveSSOFormsEventSubscriber implements EventSubscriberInterface {

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

    switch ($form_id) {
      // OpenID Connect login form.
      case 'openid_connect_login_form':
        // Change labels of client login buttons.
        $form["openid_connect_client_openathens_bluepearl_login"]["#value"] = Markup::create(t('BluePearl Login'));
        // $form["openid_connect_client_github_login"]['#value'] = Markup::create(t('GitHub Login'));

        break;
    }
  }
}
