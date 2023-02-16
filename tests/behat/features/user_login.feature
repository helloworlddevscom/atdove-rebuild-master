@api
Feature: Check that users see expected fields on login and password reset forms.

  Scenario: Anonymous user is prompted to login with email, not username.
    Given I am an anonymous user
    When I am at "/user/login"
    Then I should see the heading "Scrub In"
    And I should see the text "Email address"
    And I should not see the text "Username"
    And I should not see the text "Go to my messages"

  Scenario: Anonymous user is prompted to reset password with email, not username.
    Given I am an anonymous user
    When I am at "/user/password"
    Then I should see the heading "Reset your password"
    And I should see the text "Email address"
    And I should not see the text "Username"

  Scenario: Anonymous user is shown SSO login form.
    Given I am an anonymous user
    When I am at "/user/login"
    Then I should see the button "BluePearl Login"
    And I should see the text "Not a member yet?"

  Scenario: Administrator can see SSO login form block is properly configured.
    Given I am logged in as a user with the 'administrator' role
    When I am at '/admin/structure/block'
    Then I should see the heading 'Block layout'
    And I should see "Scrub In (disabled)" in the "#blocks" element

  Scenario: Checks for a user with no expired orgs, and that they receive active org member role on login.
    Given users:
      |name                      | mail                      | status| field_first_name | field_last_name | roles             | uid     | pass                     |
      |bobadobalina1@example.com | bobadobalina1@example.com | 1     | Bob              | Dobalina        |                   | 7712345 | bobadobalina@example.com |
    Given group entities:
      | type         | label      | field_license_status | field_current_expiration_date | id   |
      | organization | Behat-A1E0 | active               | 2028-04-05T00:00:00           | 4001 |
    And user with name "bobadobalina1@example.com" is a member of group with title "Behat-A1E0"
    When I am logged in as "bobadobalina1@example.com"
    Then I should not see the text "The license for your organization Behat-A1E0 has expired. Please update your billing information here"
    And I should not see the text "The license for your organization Behat-A1E0 has expired. Please contact your Organization Admin"
    And the user 7712345 should have the role "active_org_member"

  Scenario: Check for messaging for a user of an expired org, and that they lose active org member role on login
    Given users:
      |name                      | mail                      | status| field_first_name | field_last_name | roles             | uid     | pass                     |
      |bobadobalina2@example.com | bobadobalina2@example.com | 1     | Bob              | Dobalina2       | active_org_member | 7712346 | bobadobalina@example.com |
    Given group entities:
      | type         | label      | field_license_status | field_current_expiration_date | id   |
      | organization | Behat-A0E1 | inactive             | 2020-04-05T00:00:00           | 4002 |
    And user with name "bobadobalina2@example.com" is a member of group with title "Behat-A0E1"
    # Check for messaging for a user of an expired org, and that they lose active org member role.
    When I am logged in as "bobadobalina2@example.com"
    Then I should see the text "The license for your organization Behat-A0E1 has expired. Please contact your Organization Admin" in the "messages" region
    And I should not see the text "The license for your organization Behat-A0E1 has expired. Please update your billing information here" in the "messages" region
    And the user 7712346 should not have the role "active_org_member"

  Scenario: Check for messaging for a billing admin user of an expired org, and then they lose active org status.
    Given users:
      |name                      | mail                      | status| field_first_name | field_last_name | roles             | uid     | pass                     |
      |bobadobalina3@example.com | bobadobalina3@example.com | 1     | Bob              | Dobalina3       | active_org_member | 7712347 | bobadobalina@example.com |
    Given group entities:
      | type         | label      | field_license_status | field_current_expiration_date | id   |
      | organization | Behat-A0E1 | inactive             | 2020-04-05T00:00:00           | 4002 |
    And user with name "bobadobalina3@example.com" is a member of group with title "Behat-A0E1" and group role "admin"
    When I am logged in as "bobadobalina3@example.com"
    Then I should not see the text "The license for your organization Behat-A0E1 has expired. Please contact your Organization Admin" in the "messages" region
    And I should see the text "The subscription for your organization Behat-A0E1 has expired. Please renew your subscription here" in the "messages" region
    And the user 7712347 should not have the role "active_org_member"
