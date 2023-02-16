@api
Feature:
  Check that Opigno AtDove video activity type and the respective fields exist and are as expected.

  Scenario: AtDove video activity type and fields exist.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity_type/opigno_video/edit/fields"
    Then I should see "External video" in the "field_external_video" row
    Then I should see "Video" in the "field_video" row

  Scenario: A AtDove video activity type can be created.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity/add/opigno_video"
    And I fill in "Name" with "Test file"
    Then I press the "Save" button
    Then I should see a created successfully message
