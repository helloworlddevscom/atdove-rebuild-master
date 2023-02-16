<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing\Contracts;

use Stripe\Customer;

interface CustomerResourceInterface
{

  /**
   * @param array $customerData
   * @return Customer
   */
  public function createCustomer(array $customerData): Customer;

  public function getCustomerByID(string $customerID);

}
