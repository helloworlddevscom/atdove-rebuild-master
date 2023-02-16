<?php

namespace Drupal\Tests\Behat;

use Behat\Gherkin\Node\TableNode;
use Drupal\group\Entity\Group;
use Drupal\Tests\Behat\RawGroupDrupalContext;

/**
 * A Behat context for testing Group Content.
 */
class GroupContentContext extends RawGroupDrupalContext {

  /**
   * Creates a content node and adds it the test group. Overrides/sets the
   * given field to the given value.
   *
   * @Given there is :content_type content in my group with :value in the :field
   *
   * @param string $content_type
   * @param string $value
   * @param string $field
   */
  public function thereIsContentInMyGroupWithFieldValue(string $content_type, string $value, string $field)
  : void {
    $values = [
      $field => $value,
    ];
    $this->groupContentCreate($content_type, $values);
  }

  /**
   * Creates a content node and adds it the test group. Overrides/sets the
   * given field to the given value, and sets current user as the node author.
   *
   * @Given I create :content_type content in my group with :value in the :field
   *
   * @param string $content_type
   * @param string $value
   * @param string $field
   */
  public function iCreateContentInMyGroupWithFieldValue(string $content_type, string $value, string $field)
  : void {
    $values = [
      $field => $value,
      'uid' => $this->getUserManager()->getCurrentUser()->uid,
    ];
    $this->groupContentCreate($content_type, $values);
  }

  /**
   * Creates one or more content nodes and adds them to the test group, setting
   * current user as author. Overrides/sets the given fields with the given
   * values.
   *
   * @Given I create :content_type content in my group with:
   *
   * @param string $content_type
   * @param \Behat\Gherkin\Node\TableNode $table
   */
  public function iCreateContentInMyGroupWith(string $content_type, TableNode $table)
  : void {
    $uid = $this->getUserManager()->getCurrentUser()->uid;
    $gid = $this->getGroupManager()->getCurrentGroup()->gid;
    $this->addContentFromTable($content_type, $table, (int)$uid, (int)$gid);
  }

  /**
   * Creates one or more content nodes and adds them to the test group.
   * Overrides/sets the given fields with the given values.
   *
   * @Given there is :content_type content in my group with:
   * @Given there is :content_type content in the group with:
   *
   * @param string $content_type
   * @param \Behat\Gherkin\Node\TableNode $table
   */
  public function thereIsContentInMyGroupWith(string $content_type, TableNode $table)
  : void {
    $gid = $this->getGroupManager()->getCurrentGroup()->gid;
    $this->addContentFromTable($content_type, $table, 1, (int)$gid);
  }

  /**
   * @Given there is :content_type content with title :title in the group with label :label
   */
  public function thereIsContentWithTitleInTheGroupWithLabel(
    $content_type, $title, $label
  )
  : void {
    $values = [
      'title' => $title
    ];
    /** @var Group $group */
    $group = Group::load($this->getGroupManager()->getGroup($label)->gid);
    $this->groupContentCreate($content_type, $values , $group);
  }

  /**
   * Visits the form to create the given content type in the current group.
   *
   * @When I visit the form to create a :content_type in my group
   *
   * @param string $content_type
   */
  public function iVisitTheFormToCreateContentInMyGroup(string $content_type)
  : void {
    $gid = $this->getGroupManager()->getCurrentGroup()->gid;
    $url = "/group/$gid/content/create/group_node%3A$content_type";
    $this->getSession()->visit($this->locatePath($url));
  }

}
