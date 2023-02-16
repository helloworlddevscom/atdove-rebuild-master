@api
Feature:
  Check that Ad content type and the respective fields exist and are as expected.

  Scenario: ad content type and fields exist.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/types/manage/atdove_ad/fields"
    Then I should see text matching "body"
    Then I should see text matching "field_related_blog"
    Then I should see text matching "field_opigno_articles"
    Then I should see text matching "field_opigno_videos"

  Scenario: A ad is able to be created.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/node/add/atdove_ad"
    And I enter "Your test ad!" for "Title"
    And I press the "Save" button
    Then I should see a node created successfully message
