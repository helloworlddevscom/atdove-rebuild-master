<?php

namespace Drupal\atdove_organizations_invite\EventSubscriber;

use Drupal\ginvite\Event\UserRegisteredFromInvitationEvent;
use Drupal\ginvite\EventSubscriber\GinviteSubscriber as BaseGinviteSubscriber;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class GinviteSubscriber.
 * @package Drupal\atdove_organizations_invite\EventSubscriber
 *
 * Extends event subscriber provided by ginvite module and overrides
 * methods in order to change message text.
 */
class GinviteSubscriber extends BaseGinviteSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public function notifyAboutPendingInvitations(GetResponseEvent $event) {
    if ($this->groupInvitationLoader->loadByUser()) {
      // Exclude routes where this info is redundant or will generate a
      // misleading extra message on the next request.
      $route_exclusions = [
        'view.my_invitations.page_1',
        'ginvite.invitation.accept',
        'ginvite.invitation.decline',
      ];
      $route = $event->getRequest()->get('_route');

      if (!empty($route) && !in_array($route, $route_exclusions)) {
        $destination = Url::fromRoute('view.my_organization_invitations.page_1', ['user' => $this->currentUser->id()])->toString();
        $replace = ['@url' => $destination];
        $message = $this->t('You have pending invitations. <a href="@url">Visit your profile</a> to see them.', $replace);
        $this->messenger->addMessage($message, 'warning', FALSE);
      }
    }
  }
}
