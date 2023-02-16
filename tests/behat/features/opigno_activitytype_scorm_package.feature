@api
Feature:
  Check that Opigno AtDove scorm package activity type and the respective fields exist and are as expected.

  Scenario: AtDove Scorm Package activity type and fields exist.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity_type/opigno_scorm/edit/fields"
    Then I should see "Scorm package" in the "opigno_scorm_package" row

  @javascript
  Scenario: A Scorm Package activity type can be created.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity/add/opigno_scorm"
    And I fill in "Name" with "Test file"
    And attach the file "exampleScormPackage.zip" to "Scorm package"
    Then I press the "Save" button
    Then I should see a created successfully message
