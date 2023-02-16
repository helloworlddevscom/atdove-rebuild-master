@api
Feature:
  Check that Opigno AtDove tincan package activity type and the respective fields exist and are as expected.

  Scenario: AtDove tincan Package activity type and fields exist.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity_type/opigno_tincan/edit/fields"
    Then I should see "Tincan package" in the "opigno_tincan_package" row

  # ERROR: Impossible to create a new TinCan Package activity. Please, configure the LRS connection in the settings page.
  @javascript @wip
  Scenario: A AtDove tincan Package activity type can be created.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity/add/opigno_tincan"
    And I fill in "Name" with "Test file"
    And attach the file "exampleScormPackage.zip" to "Tincan package"
    Then I press the "Save" button
    Then I should see a created successfully message
