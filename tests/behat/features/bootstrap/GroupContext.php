<?php

namespace Drupal\Tests\Behat;

use Drupal\Tests\Behat\RawGroupDrupalContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\DrupalExtension\Context\MinkContext;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;

/**
 * Defines application features from the specific context.
 */
class GroupContext extends RawGroupDrupalContext {

  /**
   * @var \Drupal\DrupalExtension\Context\DrupalContext
   */
  protected $drupalContext;

  /**
   * @var \Drupal\DrupalExtension\Context\MinkContext
   */
  protected $minkContext;

  /**
   * @BeforeScenario
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   */
  public function gatherContexts(BeforeScenarioScope $scope)
  : void {
    $environment = $scope->getEnvironment();
    $this->drupalContext = $environment->getContext(DrupalContext::class);
    $this->minkContext = $environment->getContext(MinkContext::class);
    $this->minkContext->setMinkParameter('ajax_timeout', 5);
  }

  /**
   * Creates a test group and adds a user as a member. If a current user
   * already exists then that user will be added to the group.
   *
   * @Given I am a member of the current group
   * @Given I am a group member with the :group_role role
   * @Given I am a member of the current group with the role :group_role
   *
   * @param string $group_role
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function iAmAGroupMemberWithTheRole(string $group_role = NULL)
  : void {
    if (!$this->getGroupManager()->getCurrentGroup()) {
      // Create a group. This will be the current group.
      $this->groupCreate((object) []);
    }
    if (!$this->getUserManager()->getCurrentUser()) {
      // Create a logged-in user. This will be the current user.
      $this->drupalContext->assertAuthenticatedByRole('');
    }

    $this->addCurrentUserToCurrentGroup($group_role);
  }

  /**
   * Add the current user to the current group with the given role.
   *
   * @param string $group_role
   *   Optional - defaults to 'author'.
   */
  protected function addCurrentUserToCurrentGroup(string $group_role = NULL)
  : void {
    /** @var User $user */
    $user = User::load($this->getUserManager()->getCurrentUser()->uid);
    /** @var Group $group */
    $group = Group::load($this->getGroupManager()->getCurrentGroup()->gid);
    $group_type = $group->getGroupType()->id();

    if (is_null($group_role)) {
      $group->addMember($user);
    }
    else {
      $group->addMember($user, ['group_roles' => ["$group_type-$group_role"]]);
    }
  }

  /**
   * Creates a group of the given type and adds a user as a member.
   * If a current user already exists then that user will be added to the group.
   *
   * @Given I am a :role in a :group group
   * @Given I am a :role in a :group group type
   * @Given I am a group member of group type :type with the :role role
   *
   * @param $type
   * @param $role
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function iAmAGroupMemberOfGroupTypeWithTheRole($type, $role = NULL)
  : void {
    // Create a group. This will be the current group.
    $this->groupCreate((object) [
      'type' => $type,
    ]);
    if (!$this->getUserManager()->getCurrentUser()) {
      // Create a logged-in user. This will be the current user. Role optional.
      if (!is_null($role)) {
        $this->drupalContext->assertAuthenticatedByRole('authenticated');
      }
      else {
        $this->drupalContext->assertLoggedInByName($this->drupalContext->getRandom()->name(8));
      }
    }

    $this->addCurrentUserToCurrentGroup($role);
  }

  /**
   * @Given I visit the form to add members to the group with label :label
   *
   * @param string $label
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function iVisitTheFormToAddMembersToTheGroupWithLabel(string $label)
  : void {
    $gid = $this->getGroupIdByLabel($label);
    $path = "/group/$gid/content/add/group_membership";
    $this->getSession()->visit($this->locatePath($path));
    if (403 === $this->getSession()->getStatusCode()) {
      throw new \RuntimeException("Access denied");
    }
  }

  /**
   * Creates a group of the given type and label and visits the edit form.
   *
   * @Given I am editing a group of type :group_type with the label :label
   *
   * @param string $group_type
   * @param string $label
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function createNewGroupAndEdit(string $group_type, string $label)
  : void {
    $this->groupCreate((object)[
      'type' => $group_type,
      'label' => $label,
    ]);
    $this->iEditTheGroupWithLabel($label);
  }

  /**
   * Visit the edit page for the group having the given label.
   *
   * @When I edit the group with label :label
   *
   * @param string $label
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function iEditTheGroupWithLabel(string $label)
  : void {
    $gid = $this->getGroupIdByLabel($label);
    $path = "/group/$gid/edit";
    $this->getSession()->visit($this->locatePath($path));
  }

  /**
   * Creates a group of the given type and label and visits the group page.
   *
   * @Given I am viewing a group of type :group_type with the label :label
   * @Given I am viewing an active group of type :group_type with the title :title
   *
   * @param string $group_type
   * @param string $label
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function createNewGroupAndView(string $group_type, string $label)
  : void {
    $this->groupCreate((object) [
      'type' => $group_type,
      'label' => $label,
    ]);
    $this->iViewTheGroupWithLabel($label);
  }

  /**
   * Visit the group page having the given label.
   *
   * @When I view the group with label :label
   *
   * @param string $label
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function iViewTheGroupWithLabel(string $label)
  : void {
    $gid = $this->getGroupIdByLabel($label);
    $path = "/group/$gid";
    $this->getSession()->visit($this->locatePath($path));
  }

  /**
   * Create a group of the given type.
   *
   * @Given there is a group of type :group_type with the label :label
   * @Given there is a group of type :group_type with the title :label
   * @Given there is an active group of type :group_type with the title :label
   *
   * @param string $group_type
   * @param string $label
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function createNewGroup(string $group_type, string $label)
  : void {
    $this->groupCreate((object) [
      'type' => $group_type,
      'label' => $label,
    ]);
  }

  /**
   * Visits the edit form of the group having the given label.
   *
   * @Given I load the group with title :label
   *
   * @param string $label
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function iLoadCreatedGroup(string $label)
  : void {
    $this->iViewTheGroupWithLabel($label);
  }

  /**
   * Visits current group using its path alias.
   *
   * @Given I load the current group
   */
  public function iLoadTheCurrentGroup()
  : void {
    if ($this->getGroupManager()->getCurrentGroup()) {
      $gid = $this->getGroupManager()->getCurrentGroup()->gid;
      $group_alias = \Drupal::service('path_alias.manager')
        ->getAliasByPath("/group/$gid");
      $this->getSession()->visit($this->locatePath($group_alias));
    }
    else {
      throw new \RuntimeException('No current group set to view.');
    }
  }

  /**
   * Visits the given path relative to the current group's alias.
   *
   * @Given I view the path :relative_path relative to my current group
   * @Given I view the path :relative_path relative to the group with title :title
   *
   * @param string $relative_path
   * @param string $title
   */
  public function viewGroupPage(string $relative_path = '', string $title = NULL)
  : void {
    if ($title === NULL) {
      if ($this->getGroupManager()->getCurrentGroup()) {
        $gid = $this->getGroupManager()->getCurrentGroup()->gid;
      }
      else {
        throw new \RuntimeException('No current group set to view.');
      }
    }
    else {
      // Will throw an error on it's own if no group with title.
      $gid = $this->getGroupManager()->getGroup($title)->gid;
    }

    // Take the GID and load the group for it, load the path for the group.
    $this->getSession()->visit(
      $this->locatePath("/group/$gid/$relative_path")
    );

  }

  /**
   * @Given user with name :user_name is a member of group with title :group_title
   * @Given user with name :user_name is a member of group with title :group_title and group role :group_role
   *
   * @param string $user_name
   * @param string $group_title
   * @param string $group_role
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function userWithNameIsAMemberOfGroupWithTitle(
    string $user_name,
    string $group_title,
    string $group_role = NULL
  )
  : void {
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['name' => $user_name]);

    $groups = \Drupal::entityTypeManager()
      ->getStorage('group')
      ->loadByProperties(['label' => $group_title]);

    if (!is_array($users) || count($users) > 1) {
      throw new \RuntimeException('More than one or no users with that username found.');
    }
    if (!is_array($groups) || count($groups) > 1) {
      throw new \RuntimeException('More than one or no groups with that name found.');
    }
    if (is_array($groups) && count($groups) == 0) {
      throw new \RuntimeException("No groups were found with the title: $group_title");
    }

    /** @var User $user */
    $user = reset($users);
    /** @var Group $group */
    $group = reset($groups);
    $group_type = $group->getGroupType()->id();

    // Add member to group with optional group role.
    if (!is_null($group_role)) {
      $group->addMember($user, ['group_roles' => ["$group_type-$group_role"]]);
    }
    else {
      $group->addMember($user);
    }

  }

  /**
   * Creates groups, given the following format.
   *
   * | label    | type         | field_value_x  |
   * | My group | Organization | Whatever value |
   *
   * @Given groups:
   * @Given group:
   *
   * @param TableNode $table
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function createGroups(TableNode $table)
  : void {
    $table_hash = $table->getHash();
    foreach ($table_hash as $groupHash) {
      foreach ($groupHash as $key => $value) {
        // This matches a number representing an array index.
        // Example $key: 'field_foo[0][assoc_property]'
        preg_match('/(.+)\[(\d)]\[(.+)]/', $key, $match);
        if (isset($match[1])) {
          $groupHash[$match[1]][(int) $match[2]][$match[3]] = $value;
          unset($groupHash[$key]);
        }
      }
      $groupHash['uid'] = $groupHash['uid'] ?? 1;
      $this->groupCreate((object) $groupHash);
    }
  }

  /**
   * @Given Group memberships:
   */
  public function groupMemberships(TableNode $table)
  {
    $rows = $table->getRows();
    unset($rows[0]);

    foreach ($rows as $row) {
      $group = group::load($row[1]);
      $user = user::load($row[0]);
      $role = $row[2];
      $values = ['group_roles' => [$role]];

      if (is_null($group)) {
        $error_message = "Failed to load group with gid $row[1].";
        throw new RuntimeException($error_message);
      }

      if (is_null($user)) {
        $error_message = "Failed to load user with uid $row[0].";
        throw new RuntimeException($error_message);
      }

      $group->addMember($user, $values);
    }
  }

  /**
   * @Given the group :group has a subgroup of type :type titled :title
   */
  public function theGroupHasASubgroupOfTypeTitled($group, $type, $title)
  {
    $sub_group = $this->groupCreate((object) [
      'type' => $type,
      'label' => $title,
    ]);

    $sub_group = Group::load($sub_group->gid);

    $group = Group::load(
      $this->getGroupIdByLabel($group)
    );
    $group->addContent($sub_group, 'subgroup:' . $sub_group->bundle());
  }

  /**
   * @Then I should see :arg1 as a :arg2 autocomplete list suggestion
   */
  public function iShouldSeeAsAAutocompleteListSuggestion($arg1, $arg2)
  {
    $page = $this->getSession()->getPage();
    $autoCompleteSuggestionList = $page->findAll('css', "{$arg2} > li");
    foreach($autoCompleteSuggestionList as $suggestion) {
      if($suggestion->getText() === $arg1) {
        return true;
      };
    }
    return false;
  }

  /**
   * @Then I should not see :arg1 as a :arg2 autocomplete list suggestion
   */
  public function iShouldNotSeeAsAAutocompleteListSuggestion($arg1, $arg2)
  {
    $page = $this->getSession()->getPage();
    $autoCompleteSuggestionList = $page->findAll('css', "{$arg2} > li");
    foreach($autoCompleteSuggestionList as $suggestion) {
      if($suggestion->getText() === $arg1) {
        return false;
      };
    }
    return true;
  }

}
