<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing\Resources;


use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Drupal\atdove_billing\Contracts\CustomerResourceInterface;

class CustomerResource implements CustomerResourceInterface
{
  /**
   * @param array $customerData
   * @return Customer
   * @throws ApiErrorException
   */
    public function createCustomer(array $customerData): Customer
    {
        return Customer::create($customerData);
    }

  /**
   * Fetch a customer from stripe based on customer ID.
   *
   * @param string $customerID
   *   The customer ID to look up in stripe.
   *
   * @return Customer | bool
   *   Customer if they can be found, FALSE if not.
   *
   * @throws ApiErrorException
   */
    public function getCustomerByID(string $customerID)
    {
      try {
        $customer = Customer::retrieve($customerID);
        return $customer;
      }
      catch (ApiErrorException $e) {
        // @todo: Handle errors as nescessary.

        return FALSE;
      }
    }

}
