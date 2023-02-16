@api
Feature: Check that organization group roles are maintained as necessary for project success.

  Scenario: Members of a group change with it's active status, and remain if they have a secondary active group.
    Given group entities:
      | type         | label                 | field_license_status | id   |
      | organization | Behat ACTIVE Test Org | active               | 4001 |
      | organization | Behat STATUS Test Org | inactive             | 4002 |
    And users:
      | name                          | mail                          | status | uid  |
      | singleGroupUser1@example.com  | singleGroupUser1@example.com  | 1      | 5001 |
      | singleGroupUser2@example.com  | singleGroupUser2@example.com  | 1      | 5002 |
      | singleGroupUser3@example.com  | singleGroupUser3@example.com  | 1      | 5003 |
      | singleGroupUser4@example.com  | singleGroupUser4@example.com  | 1      | 5004 |
      | multipleGroupUser@example.com | multipleGroupUser@example.com | 1      | 5005 |
    And Group memberships:
      | uid  | gid  | roles |
      | 5005 | 4001 |       |
      | 5001 | 4002 |       |
      | 5002 | 4002 |       |
      | 5003 | 4002 |       |
      | 5004 | 4002 |       |
      | 5005 | 4002 |       |
    When I change the organization "4002" to an license status of "active"
    Then the user "5001" should have the role "active_org_member"
    Then the user "5002" should have the role "active_org_member"
    Then the user "5003" should have the role "active_org_member"
    Then the user "5004" should have the role "active_org_member"
    Then the user "5005" should have the role "active_org_member"
    When I change the organization "4002" to an license status of "inactive"
    Then the user "5001" should not have the role "active_org_member"
    Then the user "5002" should not have the role "active_org_member"
    Then the user "5003" should not have the role "active_org_member"
    Then the user "5004" should not have the role "active_org_member"
    Then the user "5005" should have the role "active_org_member"

  Scenario: An organization can have multiple org admins
    Given group:
      | type         | label                | field_license_status | id   |
      | organization | Behat Org Admin Test | active               | 5001 |
    And users:
      | name   | mail                          | status | uid  |
      | user1  | singleGroupUser1@example.com  | 1      | 9001 |
      | user2  | singleGroupUser2@example.com  | 1      | 9002 |
    Given I am logged in as a user with the 'administrator' role
    And I visit the form to add members to the group with label "Behat Org Admin Test"
    And I fill in "User" with "user1 (9001)"
    And I check the box "Org Admin"
    And I press the "Save" button
    And I visit the form to add members to the group with label "Behat Org Admin Test"
    And I fill in "User" with "user2 (9002)"
    And I check the box "Org Admin"
    And I press the "Save" button
    When I view the path "members" relative to the group with title "Behat Org Admin Test"
    Then I should see the text "Org Admin" in the "user1" row
    Then I should see the text "Org Admin" in the "user2" row
