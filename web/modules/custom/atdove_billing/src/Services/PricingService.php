<?php
/**
 * Developed by Hello World Devs
 */

namespace Drupal\atdove_billing\Services;

use Drupal\atdove_billing\Resources\PricingResource;
use Drupal\atdove_billing\Resources\ProductResource;
use Drupal\atdove_billing\PaymentConfig;
use Tightenco\Collect\Support\Collection;

class PricingService
{
    /** @var ProductResource  */
    private ProductResource $productResource;

    /** @var PricingResource  */
    private PricingResource $pricingResource;

    public function __construct(
      PricingResource $pricingResource,
      ProductResource $productResource
    )
    {
        $this->pricingResource = $pricingResource;
        $this->productResource = $productResource;
        PaymentConfig::setApiKey();
    }

  /**
   * @param int $memberCount
   * @return Collection
   * @throws \Stripe\Exception\ApiErrorException
   */
    public function getPricing(int $memberCount = 0): Collection
    {
        // Pull the product specified in the environment stripe config settings
        if(isset($_ENV['PANTHEON_ENVIRONMENT']) && ($_ENV['PANTHEON_ENVIRONMENT'] == 'live')) {
          $pricing = $this->pricingResource->getPricing(
            ['product' => \Drupal::config('stripe.settings')->get('apikey.live.product')]
          );
        } else {
          $pricing = $this->pricingResource->getPricing(
            ['product' => \Drupal::config('stripe.settings')->get('apikey.test.product')]
          );
        }

        // Filter out plans with fewer seats then the number of groups members
        return $pricing->filter(function($price) use($memberCount){
          return $this->getSeatCountFromPrice($price['seats']) >= $memberCount;
        });
    }

  /**
   * @param $id
   * @return object
   * @throws \Stripe\Exception\ApiErrorException
   */
    public function getPrice($id): object
    {
      return $this->pricingResource->getPriceById($id);
    }


    public function getSeatCountFromPrice($price)
    {
      return (int)str_replace(" Team Members", "", $price);
    }
}
