<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing\Services;

use Drupal\atdove_billing\PaymentConfig;
use Stripe\Collection;
use Stripe\Customer;
use Drupal\atdove_billing\Resources\CustomerResource;

class CustomerService
{
  /** @var CustomerResource */
  private CustomerResource $customerResource;

  public function __construct(CustomerResource $customerResource)
  {
    $this->customerResource = $customerResource;
    PaymentConfig::setApiKey();
  }

  /**
   * @param array $customerData
   * @return Customer
   * @throws \Stripe\Exception\ApiErrorException
   */
  public function createCustomer(array $customerData): Customer
  {
    return $this->customerResource->createCustomer($customerData);
  }

  /**
   * @param string $id
   * @param string $email
   * @return bool
   * @throws \Stripe\Exception\ApiErrorException
   */
  public function validateStripeID(string $id, string $email)
  {
    $valid = true; // assume valid

    $customer = $this->customerResource->getCustomerByID($id);

    if ($customer === FALSE) {
      return FALSE;
    }

    if ($customer->id !== $id) {
      $valid = false;
    }

    if($customer->email !== $email) {
      $valid = false;
    }

    return $valid;
  }

}
