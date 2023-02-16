<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing\Contracts;

use Tightenco\Collect\Support\Collection;

interface ProductResourceInterface
{
  /**
   * @param array|null $options
   * @return Collection
   */
  public function getProducts(array $options = null): Collection;
}
