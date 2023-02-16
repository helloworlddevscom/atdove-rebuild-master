@api
Feature:
  Check that Opigno AtDove interactive content activity type and the respective fields exist and are as expected.

  Scenario: AtDove Interactive Content activity type and fields exist.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity_type/opigno_h5p/edit/fields"
    Then I should see "Question" in the "opigno_h5p" row
    Then I should see "Quiz Body" in the "field_quiz_body" row
    Then I should see "Related Opigno Articles" in the "field_opigno_articles" row
    Then I should see "Related Opigno Videos" in the "field_opigno_videos" row

 Scenario: An Interactive Content activity type can be created.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity/add/opigno_h5p"
    And I fill in "Name" with "Test file"
    Then I press the "Save" button
    Then I should see a created successfully message
