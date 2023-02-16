<?php
/**
 * @file
 * Contains \Drupal\atdove_organizations_create\Form\CreateAcctOrg\CreateAcctOrgFormBase.
 */

namespace Drupal\atdove_organizations_create\Form\CreateAcctOrg;

use Drupal\atdove_billing\Services\RegistrationService;
use Drupal\atdove_billing\Services\PricingService;
use Drupal\atdove_billing\Services\CustomerService;
use Drupal\atdove_billing\Services\CardService;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\atdove_billing\BillingConstants;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class CreateAcctOrgFormBase
 * @package Drupal\atdove_organizations_create\Form\CreateAcctOrg
 *
 * A multi-step form for anonymous user to create an account, organization,
 * and send billing info to Stripe.
 *
 * Based on: https://www.codimth.com/blog/web/drupal/how-build-multi-step-forms-drupal-8
 */
abstract class CreateAcctOrgFormBase extends FormBase {
  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  private $sessionManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUserAccountProxy;

  /**
   * @var \Drupal\user\Entity\User
   */
  private $currentUser;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $store;

  /** @var PricingService  */
  private PricingService $pricingService;

  /** @var CustomerService */
  private CustomerService $customerService;

  /** @var CardService */
  private CardService $cardService;

  /** @var RegistrationService  */
  private RegistrationService $registrationService;

  /** @var string|null  */
  protected ?string $currentRouteName;

  /** @var string  */
  const MODULE = 'atdove_organizations_create';

  /** @var string  */
  const SELECT_LICENSE_ROUTE = 'atdove_organizations_create.select_license';

  const USER_ACCOUNT_ERROR = 'Oops, we\'ve enountered an error creating your account.  Please try again.';

  /**
   * Constructs a \Drupal\atdove_organizations_create\Form\CreateAcctOrg\CreateAcctOrgFormBase.
   *
   * @param PrivateTempStoreFactory $temp_store_factory
   * @param SessionManagerInterface $session_manager
   * @param AccountInterface $current_user
   * @param PricingService $pricingService
   * @param CustomerService $customerService
   * @param CardService $cardService
   * @param RegistrationService $registrationService
   * @throws \Drupal\Core\TempStore\TempStoreException
   */

  public function __construct(
    PrivateTempStoreFactory $temp_store_factory,
    SessionManagerInterface $session_manager,
    AccountInterface $current_user,
    PricingService $pricingService,
    CustomerService $customerService,
    CardService $cardService,
    RegistrationService $registrationService
  )
  {
    // Form should only be accessible to anonymous users.
    // This is currently handled on the route level in
    // adove_organizations.routing.yml. A logged in user
    // will be taken to an unhelpful access denied page.

    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUserAccountProxy = $current_user;
    $this->pricingService = $pricingService;
    $this->customerService = $customerService;
    $this->cardService = $cardService;
    $this->registrationService = $registrationService;

    $this->currentUser = User::load($current_user->id());

    $this->store = $this->tempStoreFactory->get('atdove_organizations_create_acct_org_form_data');

    $this->currentRouteName = \Drupal::routeMatch()->getRouteName();

    $price_id = \Drupal::request()->query->get('license');

    if (!empty($price_id)) {

      try {
        $price = $this->pricingService->getPrice($price_id);
      } catch(\Exception $exception) {
        \Drupal::logger(BillingConstants::MODULE)->error($exception);
        $response = new RedirectResponse(\Drupal\Core\Url::fromRoute(self::SELECT_LICENSE_ROUTE)->toString());
        $response->send();
        \Drupal::messenger()->addError(t(BillingConstants::PRICE_ERROR));
        exit; // Necessary here for the message to display on redirect.
      }

      $this->store->set('license_id', $price->id);
      $this->store->set('license_name', $price->nickname);
      $this->store->set('license_price', number_format((float)($price->unit_amount_decimal / 100), 2, '.', ','));
      $this->store->set('license_interval', $price->recurring['interval']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('session_manager'),
      $container->get('current_user'),
      $container->get('atdove_billing.pricing_service'),
      $container->get('atdove_billing.customer_service'),
      $container->get('atdove_billing.card_service'),
      $container->get('atdove_billing.registration_service')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Start a manual session for anonymous users.
    if ($this->currentUser->isAnonymous() && !isset($_SESSION['atdove_organizations_create']['create_acct_org_form_session'])) {
      $_SESSION['atdove_organizations_create']['create_acct_org_form_session'] = true;
      $this->sessionManager->start();
    }

    $form = array();
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#weight' => 10,
    );

    return $form;
  }

  /**
   * @param FormStateInterface $formState
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function saveData(FormStateInterface $formState): bool
  {
    // Create Stripe subscription
    $subscriptionData = $this->registrationService->register(
      [
        "org_name" => $this->store->get('org_name'),
        "customer_email" => $this->store->get('email'),
        "token" => $this->store->get('token'),
        "license" => $this->store->get('license_id'),
        "license_name" => $this->store->get('license_name')
      ],
    $formState,
    $this->currentRouteName);

    // Create user.
    try {
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

      $user = User::create([
        'name' => $this->store->get('email'),
        'field_first_name' => $this->store->get('first_name'),
        'field_last_name' => $this->store->get('last_name'),
        'mail' => $this->store->get('email'),
        'langcode' => $language,
        'preferred_langcode' => $language,
        'roles' => ['active_org_member']
      ]);

      // Finalize user account.
      $user->setPassword($this->store->get('password'));
      $user->activate();
      $user->save();
    } catch(\Exception $exception) {
      \Drupal::logger(self::MODULE)->error($exception);
      \Drupal::messenger()->addError(t(self::USER_ACCOUNT_ERROR));
      $formState->setRedirect($this->currentRouteName);
      return false;
    }

    // Create organization group with user as owner + stripe id, member limit, and status.
    $group_membership_limit = OrganizationsManager::discernMemberLimit(
      $subscriptionData['customer']->id,
      $subscriptionData['subscription']->metadata->license_tier,
      $subscriptionData['subscription']->plan->nickname
    );

    $new_org = OrganizationsManager::createOrg(
      $user,
      $this->store->get('org_name'),
      [
        'field_stripe_customer_id' => $subscriptionData['customer']->id,
        'field_member_limit' => $group_membership_limit,
        'field_license_status' => 'active'
      ],
    );

    // Grant user organization_admin role.
    OrganizationsManager::grantUserOrgRole($user, $new_org, 'organization-admin');

    // Send user welcome email.
    _user_mail_notify('register_no_approval_required', $user);

    // Login user.
    user_login_finalize($user);

    // Clear form data from store.
    $this->deleteStore();

    return true;
  }

  /**
   * Helper method that removes all the keys from the store collection used for
   * the multi-step form.
   *
   * There is currently no way to delete them all at once.
   * See: https://www.drupal.org/project/drupal/issues/2475719
   */
  protected function deleteStore() {
    $keys = [
      'first_name',
      'last_name',
      'email',
      'password',
      'password_confirm',
      'org_name',
      'license'
    ];
    foreach ($keys as $key) {
      $this->store->delete($key);
    }
  }
}
