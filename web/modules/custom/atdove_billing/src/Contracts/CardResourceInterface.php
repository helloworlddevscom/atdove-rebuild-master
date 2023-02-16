<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing\Contracts;

use Stripe\Card;

interface CardResourceInterface
{
  /**
   * @param string $customerId
   * @param array $source
   * @return Card
   */
  public function createCard(string $customerId, array $source): Card;
}
