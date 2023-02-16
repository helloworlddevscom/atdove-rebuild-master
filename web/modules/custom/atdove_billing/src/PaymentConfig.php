<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing;

use Stripe\Stripe;

class PaymentConfig
{
  /**
   * @return void
   */
    public static function setApiKey(): void
    {
      if(isset($_ENV['PANTHEON_ENVIRONMENT']) && ($_ENV['PANTHEON_ENVIRONMENT'] == 'live')) {
        Stripe::setApiKey(\Drupal::config('stripe.settings')->get('apikey.live.secret'));
      } else {
        Stripe::setApiKey(\Drupal::config('stripe.settings')->get('apikey.test.secret'));
      }
    }

  /**
   * @return string
   */
    public static function getPubKey(): string
    {
      if(isset($_ENV['PANTHEON_ENVIRONMENT']) && ($_ENV['PANTHEON_ENVIRONMENT'] == 'live')) {
        return \Drupal::config('stripe.settings')->get('apikey.live.public');
      } else {
        return \Drupal::config('stripe.settings')->get('apikey.test.public');
      }
    }
}
