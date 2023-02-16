@api
Feature: Check that content type homepage and the respective fields exist and are as expected.

  Scenario: All homepage fields exist and are present.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/admin/structure/types/manage/homepage/fields"
    Then I should see "Homepage Columns" in the "field_homepage_columns" row
    Then I should see "Homepage Hero" in the "field_homepage_hero" row

  Scenario: A homepage is able to be created.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/node/add/homepage"
    And I enter "First homepage test" for "Title"
    And I enter "Welcome to your home page" for "Wistia Hero Heading"
    And I fill in "URL" with "/node/add/ad"
    And I fill in "Link text" with "Create your homepage ad here"
    And I enter "first col heading" for "Col 1 Heading"
    And I enter "first col text" for "Col 1 Text"
    And I enter "second col heading" for "Col 2 Heading"
    And I enter "second col text" for "Col 2 Text"
    And I enter "third col heading" for "Col 3 Heading"
    And I enter "third col text" for "Col 3 Text"
    And I press the "Save" button
    Then I should see a node created successfully message
