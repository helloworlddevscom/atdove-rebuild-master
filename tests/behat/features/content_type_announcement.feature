@api
Feature:
  Check that Announcement content type and the respective fields exist and are as expected.

  Scenario: Announcement content type and fields exist.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/types/manage/announcement/fields"
    Then I should see text matching "body"
    Then I should see text matching "field_homepage_announcement"
    Then I should see text matching "field_insert_image"
    Then I should see text matching "field_announcement_pdf_version"
    Then I should see text matching "field_announcement_url"

  Scenario: A Announcement content type is able to be created.
    Given I am logged in as a user with the 'administrator' role
    When I am at "/node/add/announcement"
    And I enter "Grand test announcement" for "Title"
    And I press the "Save" button
    Then I should see a node created successfully message
