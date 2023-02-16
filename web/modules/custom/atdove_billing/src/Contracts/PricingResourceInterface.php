<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing\Contracts;

use Tightenco\Collect\Support\Collection;

interface PricingResourceInterface
{
  /**
   * @param array|null $options
   * @return Collection
   */
  public function getPricing(array $options = null): Collection;

  /**
   * @param string $id
   * @return object
   */
  public function getPriceById(string $id): object;
}
