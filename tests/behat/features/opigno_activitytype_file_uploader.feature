@api
Feature:
  As an admin, I want to be able to create an Opigno AtDove Video.

  Scenario: AtDove Video activity type and fields exist.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity_type/opigno_file_upload/edit/fields"
    Then I should see "Allowed extension" in the "opigno_allowed_extension" row
    Then I should see "Evaluation method" in the "opigno_evaluation_method" row
    Then I should see "Question" in the "opigno_body" row

  Scenario: A AtDove Video activity type  can be created.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity/add/opigno_file_upload"
    And I fill in "Name" with "Test file"
    Then I press the "Save" button
    Then I should see a created successfully message
