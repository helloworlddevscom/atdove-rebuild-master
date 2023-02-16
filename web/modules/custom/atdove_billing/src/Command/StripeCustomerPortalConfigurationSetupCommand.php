<?php

namespace Drupal\atdove_billing\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Stripe\BillingPortal\Configuration as StripeConfiguration;
use Drupal\atdove_billing\PaymentConfig;
use Drupal\Core\Site\Settings;

/**
 * Class StripeCustomerPortalConfigurationSetupCommand.
 *
 * Drupal\Console\Annotations\DrupalCommand (
 *     extension="atdove_billing",
 *     extensionType="module"
 * )
 */
class StripeCustomerPortalConfigurationSetupCommand extends ContainerAwareCommand {

  public function __construct()
  {
    PaymentConfig::setApiKey();
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('generate:stripe:portal_config')
      ->setAliases(['gsp'])
      ->setDescription('Configures the Stripe Customer Portal');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->getIo()->info('execute');

    $configuration = [
      'business_profile' => [
        'privacy_policy_url' => sprintf("%s/privacy-policy", Settings::get('base_url')),
        'terms_of_service_url' => sprintf("%s/terms-of-service", Settings::get('base_url')),
        'headline' => 'AtDove Online Veterinary Training'
      ],
      'features' => [
        // can the customer view their invoice history
        'invoice_history' => ['enabled' => true],
        // can the customer update their payment method
        'payment_method_update' => ['enabled' => true]
      ],
      'default_return_url' => sprintf("%s/user", Settings::get('base_url'))
    ];

    $portal = StripeConfiguration::create($configuration);

    if($portal->object = "billing_portal.configuration" && $portal->active) {
      $this->getIo()->info('Successfully configured the Stripe Customer Portal.');
      return;
    }

    $this->getIo()->info('Error configuring the Stripe Customer Portal.');
  }

}
