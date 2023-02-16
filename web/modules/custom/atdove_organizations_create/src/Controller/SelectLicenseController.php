<?php
/**
 * @file
 * Contains \Drupal\atdove_organizations_create\Controller\SelectLicenseController.
 */

namespace Drupal\atdove_organizations_create\Controller;

use Drupal\atdove_billing\BillingConstants;
use Drupal\atdove_billing\Services\PricingService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SelectLicenseController.
 *
 * (WIP) Controller to render data from Stripe
 * about licenses/subscriptions which link to the create
 * account/organization sign-up form similar to http://dev.atdove.org/future-billing.
 *
 * Based on: https://www.drupal.org/docs/drupal-apis/routing-system/introductory-drupal-8-routes-and-controllers-example
 */
class SelectLicenseController extends ControllerBase {

  /** @var PricingService  */
  private PricingService $pricingService;

  public function __construct(PricingService $pricingService)
  {
    $this->pricingService = $pricingService;
  }

  /**
   * @return array|RedirectResponse
   */
  public function content()
  {

    if(\Drupal::currentUser()->isAuthenticated()) {
      return new RedirectResponse(Url::fromRoute('<front>')->toString());
    };

    $pricing = [];

    try {
      $pricing = $this->pricingService->getPricing();
    } catch (\Exception $exception) {
      \Drupal::logger(BillingConstants::MODULE)->error($exception);
      \Drupal::messenger()->addError(t(BillingConstants::PRICING_ERROR));
    }

    return [
      // Your theme hook name.
      '#theme' => 'atdove_organizations_create__select_license',
      // Your variables.
      '#pricing' => $pricing
    ];
  }

  public static function create(ContainerInterface $container)
  {
    $pricingService = $container->get('atdove_billing.pricing_service');
    return new static($pricingService);
  }
}
