@api
Feature:
  Check that Opigno AtDove long answer activity type and the respective fields exist and are as expected.

  Scenario: AtDove long answer activity type and fields exist.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity_type/opigno_long_answer/edit/fields"
    Then I should see "Evaluation method" in the "opigno_evaluation_method" row
    Then I should see "Question" in the "opigno_body" row

  Scenario: A long answer activity type can be created.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity/add/opigno_long_answer"
    And I fill in "Name" with "Test file"
    Then I press the "Save" button
    Then I should see a created successfully message
