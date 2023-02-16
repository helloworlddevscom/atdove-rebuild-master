<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing\Resources;

use Drupal\atdove_billing\Contracts\PricingResourceInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Tightenco\Collect\Support\Collection;

class PricingResource implements PricingResourceInterface
{
  /** @var Collection  */
  private Collection $collection;

  public function __construct()
  {
    $this->collection = new Collection();
  }

  /**
   * @param array|null $options
   * @return Collection
   * @throws ApiErrorException
   */
  public function getPricing(array $options = null): Collection
  {

    $pricing = Price::all($options);

    if(is_null($pricing) || is_null($pricing['data'])) {
      return $this->collection;
    }

    foreach($pricing['data'] as $price) {
      // only load prices that have a metadata key pair equal to ['type' => 'atdove-site']
      if($price->metadata['type'] === "atdove-site") {
        $this->collection->add([
          'id' => $price->id,
          'seats' => $price->nickname,
          'amount' => $price->unit_amount,
          'interval' => $price->recurring->interval
        ]);
      }
    }
    $this->collection = $this->collection->sortBy('amount');
    $this->collection = $this->collection->map(function($item, $key) {
      $item['amount'] = number_format((float)($item['amount'] / 100), 2, '.', ',');
      return $item;
    });
    return $this->collection;
  }

  /**
   * @param string $id
   * @return object
   * @throws ApiErrorException
   */
  public function getPriceById(string $id): object
  {
    return Price::retrieve($id);
  }
}
