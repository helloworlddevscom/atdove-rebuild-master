@api
Feature: Check that content type survey and the respective fields exist and are as expected.

  Scenario: All survey fields exist and are present.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/admin/structure/types/manage/survey/fields"
    Then I should see "Body" in the "body" row
    Then I should see "Instructions" in the "field_instructions" row
    Then I should see "Related Blog Entries" in the "field_related_blog" row
    Then I should see "Related Opigno Articles" in the "field_opigno_articles" row
    Then I should see "Related Opigno Videos" in the "field_opigno_videos" row
    Then I should see "Thank You Message" in the "field_thank_you_message" row

  Scenario: A survey is able to be created.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/node/add/survey"
    And I enter "First survey test" for "Title"
    And I press the "Save" button
    Then I should see a node created successfully message


