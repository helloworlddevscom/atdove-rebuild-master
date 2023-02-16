@api
Feature: Check that content type basic page and the respective fields exist and are as expected.

  Scenario: All basic page fields exist and are present.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/admin/structure/types/manage/page/fields"
    Then I should see "Body" in the "body" row
    Then I should see "Layout" in the "layout_builder__layout" row
    Then I should see "Paragraphs" in the "field_paragraphs" row

  Scenario: A basic page is able to be created.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/node/add/page"
    And I enter "basic page test" for "Title"
    And I press the "Save" button
    Then I should see a node created successfully message
