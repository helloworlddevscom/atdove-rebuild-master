@api
Feature:
  Check that Opigno AtDove slide activity type and the respective fields exist and are as expected.

  Scenario: AtDove slide activity type and fields exist.
    Given I am logged in as a user with the "administrator" role
    When I am at "/admin/structure/opigno_activity_type/opigno_slide/edit/fields"
    Then I should see "Content" in the "opigno_body" row
    Then I should see "PDF" in the "opigno_slide_pdf" row

  Scenario: A AtDove slide activity type can be created.
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/opigno_activity/add/opigno_slide"
    And I fill in "Name" with "Test file"
    Then I press the "Save" button
    Then I should see a created successfully message
