<?php

namespace Drupal\atdove_opigno\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\user\Entity\User;

/**
 * Provides a 'Message User' Block.
 *
 * @Block(
 *   id = "atdove_user_message_block",
 *   admin_label = @Translation("AtDove User Message"),
 *   category = @Translation("atdove"),
 * )
 */
class UserMessageBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_user = \Drupal::currentUser();
    $user_param = \Drupal::routeMatch()->getParameter('user');
    $target_uid = 0;

    if (is_string($user_param)) {
      $target_uid = intval($user_param);
    }
    elseif(is_object($user_param) && method_exists($user_param, 'id')) {
      $target_uid = $user_param->id();
    }

    if (
      intval($current_user->id()) !== intval($target_uid)
    ) {
      $markup = '<a class="link-box" href="/opigno-messaging/redirect-new-thread-with-user/' . $target_uid  . '">Send user a message</a>';
    }
    else {
      $markup = '<a class="link-box" href="/private-messages/">Go to my messages</a>';
    }

    // Default return if no user.
    return [
      '#markup' => $markup,
    ];
  }

  /**
   * Add a cache tag for each user for the block cache tags when rendering.
   */
  public function getCacheTags() {
    if ($user = \Drupal::routeMatch()->getParameter('user')) {
      if (!is_object($user)) {
        $user = User::load($user);
      }
      return Cache::mergeTags(parent::getCacheTags(), array('user:' . $user->id()));
    } else {
      return parent::getCacheTags();
    }
  }

  /**
   * Add route cache context to the block.
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'route',
      'url.path'
    ]);
  }

}
