<?php

namespace Drupal\Tests\Behat;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Gherkin\Node\TableNode;
use Drupal\group\Entity\GroupContent;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\atdove_organizations\OrganizationsManager;
use Drupal\group\Entity\Group;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use RuntimeException;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

  /**
   * Checks against Drupal if the module is enabled.
   *
   * Scenario: Checking that 'Module plain text name' is enabled at
   *   "admin/modules" Then the module "machine_name" should be enabled
   *
   * @Given /^The module "(?P<module>[^"]*)" should be enabled$/
   * @Given /^the module "(?P<module>[^"]*)" should be enabled$/
   *
   * @param $module
   */
  public function assertModuleIsEnabled($module)
  : void {
    if (!\Drupal::moduleHandler()->moduleExists($module)) {
      throw new \RuntimeException("The module '{$module}' is not enabled.");
    }
  }

  /**
   * @When /^I wait for the page to be loaded$/
   */
  public function waitForThePageToBeLoaded()
  {
    $this->getSession()->wait(10000, "document.readyState === 'complete'");
  }

  /**
   * @Then I switch to iframe :arg1
   */
  public function iSwitchToIframe($iframeSelector)
  {
    $this->getSession()->getDriver()->switchToIFrame($iframeSelector);
  }

  /**
   * @Then I switch back to the main window.
   */
  public function switchBackToMainWindow()
  {
    $this->getSession()->getDriver()->switchToWindow();
  }

  /**
   * Waits a while, for slow processes
   *
   * @param int $seconds
   *   How long to wait.
   *
   * @When I wait :seconds second(s)
   */
  public function wait($seconds) {
    sleep($seconds);
  }

  /**
   * @When I visit the one time login link for user with id :uid
   *
   * Loads and visits a onetime login link.
   *
   * @param int $uid
   *   The user ID to load a one time login link for and visit.
   */
  public function getOneTimeLoginUrl(int $uid) {
    if (\Drupal::currentUser()->id() !== 0){
      throw new \RuntimeException("User is already logged in.");
    }

    if ($user = user::load($uid)) {
      $timestamp = time();
      $one_time_login_ink =
        Url::fromRoute(
          'user.reset',
          [
            'uid' => $uid,
            'timestamp' => $timestamp,
            'hash' => user_pass_rehash($user, $timestamp),
          ],
          [
            'absolute' => true,
            'language' => \Drupal::languageManager()->getLanguage($user->getPreferredLangcode()),
            // The base URL is derived by the Symfony request handler from
            // the global variables set by the web server, i.e. REQUEST_URI
            // or similar. Since Behat tests are run from the command line
            // this request context is not available and we need to set the
            // base URL manually.
            // @todo Remove this workaround once this is fixed in core.
            // @see https://www.drupal.org/project/drupal/issues/2548095
            'base_url' => $GLOBALS['base_url'],
          ]
        )->toString()
      ;

      $this->getSession()->visit($this->locatePath($one_time_login_ink));

      // If available, add extra validation that this is a 200 response.
      $status_code = $this->getSession()->getStatusCode();

      if ($status_code !== 200) {
        throw new \RuntimeException("Onetime login link ($one_time_login_ink) failed with status code ($status_code");
      }

    }
    else {
      throw new \RuntimeException("Unable to load user with UID : " . $uid);
    }

  }

  /**
   * @Then the user :user_name should have an :status organization
   */
  public function userHasActiveOrg($user_name, $status) {
    $manager = $this->getUserManager();

    // Get referenced user.
    $uid = $manager->getUser($user_name)->uid;
    $user = user::load($uid);
    $orgs = OrganizationsManager::getUserOrgs($user);

    var_dump(count($orgs));

    // @todo Finish this psuedo coded section.
    // Load group for user
    // Load groups field_license_status
    // $group_status = $group->field_license_status->value
    // if ($status !== field_license_status->value)
    // throw new \RuntimeException("Expected status of "$status" but found status of "");
  }

  /**
   * @Then I switch to iframe via css selector :selector
   */
  public function switchToIframeCcsSelector($selector) {
    $session = $this->getSession();
    $iframe = $session->getPage()->find('css', $selector);

    if ($iframe) {
      $session->switchToIFrame($iframe->getAttribute('name'));
    }
    else {
      throw new \RuntimeException("Unable to find iframe with selector: " . $selector);
    }
  }

  /**
   * @Then I should see a node updated successfully message
   * @Then I should see a node created successfully message
   * @Then I should see an updated successfully message
   * @Then I should see a created successfully message
   */
  public function updateCreateSuccessMessage() {
    if (
      $this->searchForDrupalMessage('updated') === FALSE
      && $this->searchForDrupalMessage('created') === FALSE
    ) {
      $drupal_messages = $this->getDrupalMessages();
      $message = "No success message (created/updated) was found in the messages region. Message says: " . $drupal_messages;
      throw new \RuntimeException($message);
    }
  }

  /**
   * @Then I should not see a node updated successfully message
   * @Then I should not see a node created successfully message
   * @Then I should not see an updated successfully message
   * @Then I should not see a created successfully message
   */
  public function updateCreateNoSuccessMessage() {
    if (
      $this->searchForDrupalMessage('updated') !== FALSE
      && $this->searchForDrupalMessage('created') !== FALSE
    ) {
      $message = "Success message (created/updated) was found in the messages region.";
      throw new \RuntimeException($message);
    }
  }

  /**
   * @Then I should see a node deleted successfully message
   * @Then I should see a deleted successfully message
   */
  public function deletedSuccessMessage() {
    if ($this->searchForDrupalMessage('deleted') === FALSE) {
      $message = "No deleted success message (deleted) was found in the messages region.";
      throw new \RuntimeException($message);
    }
  }

  /**
   * Consolidated code checking for the presence of a message in a specifc drupal message element.
   */
  private function searchForDrupalMessage($message) {
    return strpos(strtolower($this->getDrupalMessages()), $message) !== FALSE;
  }

  /**
   * Helper function to get the drupal messages.
   *
   * @return string
   *   The text from the drupal messages region.
   */
  private function getDrupalMessages() : string {
    $page = $this->getSession()->getPage();

    $admin_messages = $page->find('css', 'div[data-drupal-messages]') ?: $page->find('css', 'div[data-drupal-messages-fallback] .messages');
    $regular_messages = $page->find('css', 'div.messages');

    if (!$admin_messages && !$regular_messages) {
      $error_message = "The messages region was not found on the page.";
      throw new \RuntimeException($error_message);
    }

    $messages = '';

    if (method_exists($admin_messages, 'getText')) {
      $messages .= $admin_messages->getText();
    }

    if (method_exists($regular_messages, 'getText')) {
      $messages .= $regular_messages->getText();
    }

    return $messages;
  }

  /**
   * Checks search results.
   *
   * @param tableNode $expectedSearchResults
   *   A single column of the order in which search results should be.
   *
   * EXAMPLE:
   *   | search result 2 |
   *   | search result 3 |
   *   | search result 1 |
   *
   * @Then the search results order should be:
   */
  public function theSearchResultsOrderShouldBe(TableNode $expectedSearchResults)
  {
    $page = $this->getSession()->getPage();
    $search_results = $page->find('css', 'div.view-display-id-search_results_page');
    $searchRows = $search_results->findAll('css', 'div.view-display-id-search_results_page .views-row');

    // Check that there are any results.
    if (!$search_results || count($searchRows) == 0) {
      $error_message = "No search results were found on the current page";
      throw new \RuntimeException($error_message);
    }

    // Check that results number matches passed values.
    if (count($expectedSearchResults->getRows()) !== count($searchRows)) {
      $error_message = "The amount of search rows provided did not match the amount of rows found. ";
      $error_message .= "Found " . count($searchRows) . " rows but was provided " . count($expectedSearchResults->getRows()) . " rows.";
      throw new \RuntimeException($error_message);
    }

    // Check that the order matches.
    foreach ($searchRows as $key => $searchRow) {
      $title_text = $searchRow->find('css', '.views-field-title')->getText();
      if (
        $title_text
        !== $expectedSearchResults->getRow($key)[0]
      ) {
        $error_message = "Row " . ($key + 1) . " did not contain the text provided in the table.";
        throw new \RuntimeException($error_message);
      }
    }
  }

  /**
   * @todo: allow accepting GID (INT) or NAME (STRING).
   *
   * @When I change the organization :gid to an license status of :status
   */
  public function iChangeTheOrganizationToAnLicenseStatusOf($gid, $status)
  {
    $group = group::load($gid);

    if (is_null($group)) {
      $error_message = "Failed to load group with gid $gid.";
      throw new \RuntimeException($error_message);
    }

    $group->set('field_license_status', $status);
    $group->save();
  }

  /**
   * Helper function to determine if a user has a role or not.
   *
   * @param int $uid
   *   The user to load by their UID.
   * @param string $role
   *   The role to check that the user has.
   *
   * @return bool
   *   TRUE if user has role. FALSE if user does not have role.
   */
  private function userHasRole($uid, $role) {
    $user = user::load($uid);

    if (is_null($user)) {
      $error_message = "Failed to load user with uid $uid.";
      throw new \RuntimeException($error_message);
    }

    return $user->hasRole($role);
  }

  /**
   * @Then the user :uid should have the role :role
   */
  public function theUserShouldHaveTheRole($uid, $role)
  {
    if (!$this->userHasRole($uid, $role)) {
      $error_message = "User with uid $uid did not have the role $role.";
      throw new \RuntimeException($error_message);
    }
  }

  /**
   * @Then the user :uid should not have the role :role
   */
  public function theUserShouldNotHaveTheRole($uid, $role)
  {
    if ($this->userHasRole($uid, $role)) {
      $error_message = "User with uid $uid did infact have the role $role.";
      throw new \RuntimeException($error_message);
    }
  }

  /**
   * Clicks an element, found by CSS selector after scrolling 200 pixels above.
   *
   * @param string $selector
   *   The CSS selector.
   *
   * @When I click the :selector element after scrolling to just above it
   */
  public function clickScrollAbove($selector) {
    $element = $this->assertSession()->elementExists('css', $selector);

    try {
      $this->getSession()->executeScript('document.querySelector("' . addslashes($selector) . '").scrollIntoView()');
      $this->getSession()->executeScript('window.scrollBy(0,-200)');
    }
    catch (UnsupportedDriverActionException $e) {
      // Don't worry about it. Or so the code I duplicated says...
    }
    $element->click();
  }

  /**
   * @When /^I click "([^"]*)" on the row containing "([^"]*)"$/
   *
   * @param string $linkName
   * @param string $rowText
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function iClickOnOnTheRowContaining(string $linkName, string $rowText)
  : void {
    /** @var $row \Behat\Mink\Element\NodeElement */
    $rows = $this->getSession()
      ->getPage()
      ->findAll('css', "table tr:contains('{$rowText}')");
    if (!$rows || empty($rows)) {
      throw new ElementNotFoundException("Cannot find any row on the page containing the text '{$rowText}'");
    }
    foreach ($rows as $row) {
      if ($row->findLink($linkName)) {
        $row->clickLink($linkName);
        return;
      }
    }
    throw new ElementNotFoundException("Found row(s) with '{$rowText}' but couldn't find a link in them with '{$linkName}'");
  }

  /**
   * @Then I set the state :key to :value
   */
  public function setStateToValue($key, $value) {
    \Drupal::state()->set($key, $value);
  }

  /**
   * @Then I should see the non case sensitive heading :heading
   */
  public function assertHeadingNonCaseSensitive($heading)
  {
    $element = $this->getSession()->getPage();
    foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
      $results = $element->findAll('css', $tag);
      foreach ($results as $result) {
        if (strtolower($result->getText()) == strtolower($heading)) {
          return;
        }
      }
    }
    throw new \Exception(sprintf("The text '%s' was not found in any heading on the page %s", $heading, $this->getSession()->getCurrentUrl()));
  }

  /**
   * @Then the field :element should have a value of :value
   * @Then :element should have a value of :value
   * @Then :element should have the value :value
   *
   * https://github.com/minkphp/Mink/issues/215
   */
  public function iShouldSeeValueElement($element, $value) {
    $page = $this->getSession()->getPage();
    // Alternately, substitute with getText() for the label.
    $element_value = $page->findField($element)->getValue();

    if ($element_value != $value) {
      throw new \Exception("Value $value not found in element $element which instead has a value of $element_value");
    }
  }

  /**
   * Checks for field rows at admin/structure/types/manage/TYPE/fields path.
   *
   * | Field Label    | Machine Name    | Field type   |
   * | Ex Field Label | ex_field_label  | Text (plain) |
   *
   * @Then I should see the following field config rows:
   *
   * @param TableNode $table
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function seeFieldConfigRows(TableNode $table) {

    $table_hash = $table->getHash();
    foreach ($table_hash as $row_hash) {
      var_dump($row_hash);
    }

  }

  /**
   * @Then I clean up the user :user_name and their groups
   */
  public function cleanUpUserAndGroups($user_name)
  {
    // Load user
    $user = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties([
        'name' => $user_name,
      ]);

    if (!is_array($user)) {
      var_dump($user);
      throw new \Exception("Failed to load user! Outputting user var return value above.");
    }
    if (count($user) === 0) {
      throw new \Exception("No user found with name: $user_name");
    }
    elseif(count($user) > 1) {
      throw new \Exception("More than one users found with name: $user_name");
    }

    // Safe to say we have one user, load user.
    $user = reset($user);

    // Load and delete all groups.
    $grp_membership_service = \Drupal::service('group.membership_loader');
    $grps = $grp_membership_service->loadByUser($user);
    foreach ($grps as $grp) {
      $grp->getGroup()->delete();
    }
    // Delete user.
    $user->delete();
  }

  /**
   * @Then I fill in wysiwyg on field :locator with :string
   * @Then I fill in the wysiwyg on field :locator with :string
   */
  public function iFillInWysiwygOnFieldWith($locator, $string) {
    $el = $this->getSession()->getPage()->findField($locator);

    if ($el === NULL) {
      throw new RuntimeException("Could not find WYSIWYG with locator '{$locator}'");
    }

    $fieldId = $el->getAttribute('id');
    if (empty($fieldId)) {
      throw new RuntimeException("Could not find WYSIWYG with locator '{$locator}'");
    }

    $this->getSession()
      ->executeScript("CKEDITOR.instances[\"$fieldId\"].setData(\"$string\");");
  }

  /**
   * @When the user :user_name has an invite to group with name :group_name
   */
  public function theUserHasAnInviteToGroupWithName($user_name, $group_name) {
    // Load user.
    $user = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties([
        'name' => $user_name,
      ]);

    if (count($user) !== 1) {
      throw new RuntimeException("Could not load user with user name: $user_name");
    }

    $user = reset($user);

    // Load group.
    $group = \Drupal::entityTypeManager()
      ->getStorage('group')
      ->loadByProperties([
        'label' => $group_name,
      ]);

    if (count($group) !== 1) {
      throw new RuntimeException("Could not load group with group name: $group_name");
    }

    $group = reset($group);

    // Pre-populate a group membership with the current user.
    $group_membership = GroupContent::create([
      'type' => 'organization-group_invitation',
      'email' => $user->getEmail(),
      'entity_id' => $user->id(),
      'content_plugin' => 'group_membership',
      'gid' => $group->id(),
      'uid' => 1,
      'group_roles' => [],
    ]);

    $group_membership->save();

  }

  /**
   * @Then I should see the text :text in the view with heading :view
   *
   * @param $text
   * @param $view
   *
   * @throws \Exception
   */
  public function iShouldSeeTheTextInTheView($text, $view)
  : void {
    if (!$this->textInTheView($text, $view, TRUE)) {
      $error =  "The text {$text} was NOT found in the view with heading {$view}";
      throw new RuntimeException($error);
    }
  }

  /**
   * @Then I should not see the text :arg1 in the view with heading :arg2
   *
   * @param string $text
   * @param string $heading
   */
  public function iShouldNotSeeTheTextInTheView(string $text, string $heading)
  : void {
    if (!$this->textInTheView($text, $heading, FALSE)) {
      $error = "The text '{$text}' WAS found in the view with heading '{$heading}'";
      throw new RuntimeException($error);
    }
  }

  /**
   * Checks for text either in or not in a view.
   *
   * @param string $arg1
   *   The text to look for in the view.
   * @param string $arg2
   *   The view heading text to search for to determine the view.
   * @param bool $conditionCheck
   *   TRUE or FALSE, should the text be present (True if so, false if not)
   *
   * @return bool
   *   TRUE if found, false if not but generally throws exception first.
   *
   * @throws \RuntimeException
   */
  private function textInTheView(string $arg1, string $arg2, bool $conditionCheck)
  : bool {
    $error = FALSE;

    // Grab the page, and all views on the page.
    $page = $this->getSession()->getPage();
    $views = $page->findAll('css', '.views-element-container');
    $viewFound = FALSE;

    // Iterate over all of the views.
    foreach ($views as $view) {
      if ($view->find('css', 'h2')) {
        $heading = $view->find('css', 'h2')->getHtml();
        // Do we have a view?
        if (str_contains($heading, $arg2)) {
          $viewFound = TRUE;
          $textFound = strpos($view->getText(), $arg1);
          if ($conditionCheck === TRUE) {
            // Returns whether the text was found or not essentially.
            return $textFound !== FALSE;
          }
          // Returns whether the the text was NOT present.
          return $textFound === FALSE;
        }
      }
    }

    // Possibly made it here since there was no view.
    if (!$viewFound) {
      throw new RuntimeException("No view with heading '{$arg2}' was found.");
    }

    return FALSE;
  }

}
