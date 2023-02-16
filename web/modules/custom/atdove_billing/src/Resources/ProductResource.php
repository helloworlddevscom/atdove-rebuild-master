<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing\Resources;

use Tightenco\Collect\Support\Collection;
use Drupal\atdove_billing\Contracts\ProductResourceInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\Product;

class ProductResource implements ProductResourceInterface
{
  /** @var Collection  */
  private $collection;

  public function __construct()
  {
    $this->collection = new Collection();
  }

  /**
   * @param array|null $options
   * @return Collection
   * @throws ApiErrorException
   */
  public function getProducts(array $options = null): Collection
  {
    $products = Product::all($options);

    foreach($products as $product) {
      $this->collection->add($product);
    }
    return $this->collection;
  }
}
