@api
Feature: You can perform all essentials of managing users as admin.

  # @todo admin can create a user

  # @todo admin can edit a user
  Scenario: As an admin I can EDIT users from the /admin/people UI
    Given users:
      | name                       | mail                       | status| uid    | field_first_name | field_last_name |
      | deleteme-behat@example.com | deleteme-behat@example.com | 1     | 999999 | DELETE           | ME              |
    When I am logged in as a user with the "administrator" role
    And I am at "admin/people"
    And I click "Edit" on the row containing "deleteme-behat@example.com"
    And I fill in "First name" with "Testig"
    And I press the "Save" button
    Then I should see the text "The changes have been saved." in the "messages" region

  # @todo admin can delete a user from the people UI
  Scenario: As an admin I can delete users from the /admin/people UI
    Given users:
      | name                       | mail                       | status| uid    | field_first_name | field_last_name |
      | deleteme-behat@example.com | deleteme-behat@example.com | 1     | 999999 | DELETE           | ME              |
    When I am logged in as a user with the "administrator" role
    And I am at "admin/people"
   # @todo Select the user
   # @todo Select cancel user from drop down.
   # @todo... go through process and confirm there is no row for the deleteme user at "admin/people"

  # @todo admin can delete a user from the user edit UI
