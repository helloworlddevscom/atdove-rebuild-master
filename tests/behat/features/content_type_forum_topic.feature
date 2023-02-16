@api
Feature: Check that content type forum topics and the respective fields exist and are as expected.

  Scenario: All Forum topic fields exist and are present.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/admin/structure/types/manage/forum/fields"
    Then I should see "Body" in the "body" row
    Then I should see "Comments" in the "comment_forum" row
    Then I should see "Forums" in the "taxonomy_forums" row

  Scenario: A forum topic is able to be created.
    Given I am logged in as a user with the 'administrator' role
    Given "forums" terms:
      | name    | tid  |
      | default | 9999 |
    When I am at "/node/add/forum"
    And I enter "Test Forum topic " for "Subject"
    And I select "default" from "Forums"
    And I press the "Save" button
    Then I should see a node created successfully message
