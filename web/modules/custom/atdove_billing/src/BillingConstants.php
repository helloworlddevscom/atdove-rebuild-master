<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing;


class BillingConstants
{
  /** @var string  */
  public const MODULE = 'atdove_billing';

  /** @var string  */
  public const SUBSCRIPTION_ERROR = 'Oops, we\'ve encountered an error processing your subscription.  Please try again.';

  /** @var string  */
  public const PRICING_ERROR = 'Oops, we\'ve encountered an error loading subscription options.  Please reload the page to try again.';

  /** @var string */
  public const PRICE_ERROR = 'Oops, we\'ve encountered an error loading your selection.  Please try again.';
}
