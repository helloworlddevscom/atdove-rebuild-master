@api
Feature: Members can be added and removed from a group as expected

  Scenario: Admin can add a user to a group and delete users from a group
    Given users:
      | name                       | mail                       | status| uid    | field_first_name | field_last_name |
      | deleteme-behat@example.com | deleteme-behat@example.com | 1     | 999999 | DELETE           | ME              |
    # NOTE: Intentionally use a stripe value that does not bypass stripe so we can be sure stripe isn't triggered here incorrectly.
    And group entities:
      | type         | label          | field_stripe_customer_id | id |
      | organization | Behat Test Org | BEHAT_PASS               | 9999 |
    Given I am logged in as a user with the 'administrator' role
    When I am at "/group/9999/members"
    Then I should not see the text "deleteme-behat@example.com" in the view with heading "Behat Test Org"
    And I click "Add member"
    And for "User" I enter "deleteme-behat@example.com (999999)"
    Then I press the "Save" button
    Then I should see the text "DELETE ME" in the view with heading "Behat Test Org"
    And I click "Remove Member" on the row containing "DELETE ME"
    And I press the "Delete" button
    Then I should not see the text "deleteme-behat@example.com" in the view with heading "Behat Test Org"

  # The user having the primary stripe email is spoofed in code and requires BEHAT_FAIL to mimic the result
  Scenario: Admin CANNOT remove a user who is the stripe billing ID
    Given users:
      | name                     | mail                     | status| uid    | field_first_name | field_last_name |
      | stripe-admin@example.com | stripe-admin@example.com | 1     | 999999 | DELETE           | ME              |
    # NOTE: Intentionally use a stripe value that does not bypass stripe so we can be sure stripe isn't triggered here incorrectly.
    And group entities:
      | type         | label          | field_stripe_customer_id | id |
      | organization | Behat Test Org | BEHAT_FAIL               | 9999 |
    Given I am logged in as a user with the 'administrator' role
    When I am at "/group/9999/members"
    Then I should not see the text "deleteme-behat@example.com" in the view with heading "Behat Test Org"
    And I click "Add member"
    And for "User" I enter "deleteme-behat@example.com (999999)"
    Then I press the "Save" button
    Then I should see the text "DELETE ME" in the view with heading "Behat Test Org"
    And I click "Remove Member" on the row containing "DELETE ME"
    Then I should see the text "You cannot remove this user as their email is currently associated with this Organization's stripe account"

  Scenario: Remove member form does not die if a stripe customer account cannot be found (invalid being the same result currently as stripe cannot be reached).
    Given users:
      | name                     | mail                     | status| uid    | field_first_name | field_last_name |
      | stripe-admin@example.com | stripe-admin@example.com | 1     | 999999 | DELETE           | ME              |
    And group entities:
      | type         | label          | field_stripe_customer_id       | id |
      | organization | Behat Test Org | this_will_fail_customer_lookup | 9999 |
    Given I am logged in as a user with the 'administrator' role
    When I am at "/group/9999/members"
    Then I should not see the text "deleteme-behat@example.com" in the view with heading "Behat Test Org"
    And I click "Add member"
    And for "User" I enter "deleteme-behat@example.com (999999)"
    Then I press the "Save" button
    Then I should see the text "DELETE ME" in the view with heading "Behat Test Org"
    And I click "Remove Member" on the row containing "DELETE ME"
    Then I should not see the text "You cannot remove this user as their email is currently associated with this Organization's stripe account"
    And I should see the "Delete" button

  Scenario: Only users of selected group are returned by group add member page user field autocomplete
    Given users:
      | name                       | mail                       | status| uid    | field_first_name | field_last_name |
      | user1-behat@example.com    | user1-behat@example.com    | 1     | 999997 | USER             | ONE             |
      | user2-behat@example.com    | user2-behat@example.com    | 1     | 999998 | USER             | TWO             |
      | user3-behat@example.com    | user3-behat@example.com    | 1     | 999999 | USER             | THREE            |
    And group entities:
      | type         | label          | field_stripe_customer_id | id |
      | organization | Behat Test Org | BEHAT_PASS               | 9999 |
    Given user with name "user1-behat@example.com" is a member of group with title "Behat Test Org"
    And I am logged in as a user with the 'administrator' role
    When I am at "/group/9999/members"
    And I click "Add member"
    And for "User" I enter "user1-behat@example.com"
    Then I should see "USER ONE (999997)" as a "ui-id-1" autocomplete list suggestion
    And for "User" I enter "user2-behat@example.com"
    Then I should not see "USER TWO (999998)" as a "ui-id-1" autocomplete list suggestion
    And for "User" I enter "user3-behat@example.com"
    Then I should not see "USER THREE (999999)" as a "ui-id-1" autocomplete list suggestion
