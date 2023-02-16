<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing\Resources;

use Stripe\Customer;
use Stripe\Card;
use Stripe\Exception\ApiErrorException;
use Drupal\atdove_billing\Contracts\CardResourceInterface;

class CardResource implements CardResourceInterface
{
  /**
   * @param string $customerId
   * @param array $source
   * @return Card
   * @throws ApiErrorException
   */
    public function createCard(string $customerId, array $source): Card
    {
      return Customer::createSource($customerId, $source);
    }
}
