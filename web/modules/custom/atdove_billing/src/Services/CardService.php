<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing\Services;

use Drupal\atdove_billing\PaymentConfig;
use Stripe\Card;
use Drupal\atdove_billing\Resources\CardResource;

class CardService
{
  /** @var CardResource */
  private CardResource $cardResource;

  public function __construct(CardResource $cardResource)
  {
    $this->cardResource = $cardResource;
    PaymentConfig::setApiKey();
  }

  /**
   * @param string $customerID
   * @param array $source
   * @return Card
   * @throws \Stripe\Exception\ApiErrorException
   */
  public function createSource(string $customerID, array $source): Card
  {
    return $this->cardResource->createCard($customerID, $source);
  }

}
