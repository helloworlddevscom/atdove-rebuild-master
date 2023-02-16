@api
Feature: Check that all user variations can or cannot access stripe billing for given groups.

  Scenario: Non org admins CANNOT see the manage billing links
    Given group entities:
      | type         | label        | field_license_status | field_current_expiration_date | field_stripe_customer_id | id    |
      | organization | Behat-A1E0S1 | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 40001 |
    And users:
      | name                 | mail                 | status | uid    | roles             |
      | testing@example.com  | testing@example.com  | 1      | 500001 | active_org_member |
    And user with name "testing@example.com" is a member of group with title "Behat-A1E0S1"
    When I am logged in as "testing@example.com"
    And I am at "/group/40001"
    Then I should not see the link "Manage Billing"
    And I should not see the link "Billing"

  Scenario: Org admins can see the group menu links correctly and they are not cache between users and/or groups.
    Given group entities:
      | type         | label        | field_license_status | field_current_expiration_date | field_stripe_customer_id  | id    |
      | organization | Behat1-A1E0S1 | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 40001 |
      | organization | Behat2-A1E0S1 | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 40002 |
    And users:
      | name                 | mail                 | status | uid    | roles             |
      | testing1@example.com | testing@example.com  | 1      | 500001 | active_org_member |
      | testing2@example.com | testing@example.com  | 1      | 500002 | active_org_member |
    And user with name "testing1@example.com" is a member of group with title "Behat1-A1E0S1" and group role "admin"
    And user with name "testing1@example.com" is a member of group with title "Behat2-A1E0S1" and group role "admin"
    And user with name "testing2@example.com" is a member of group with title "Behat1-A1E0S1" and group role "admin"
    # Check initial rendering and link
    When I am logged in as "testing1@example.com"
    And I am at "/group/40001"
    Then I should see the heading "Behat1-A1E0S1"
    # check members works fine, in theory could be cached onward for other groups/users here.
    And I click "Members"
    Then I should see the heading "Behat1-A1E0S1 members"
    # CHeck members link works in another group
    And I am at "/group/40002"
    Then I should see the heading "Behat2-A1E0S1"
    And I click "Members"
    Then I should see the heading "Behat2-A1E0S1 members"
    # Check links work as expected for another user in a different group than last user tested last.
    And I am not logged in
    When I am logged in as "testing2@example.com"
    And I am at "/group/40001"
    Then I should see the heading "Behat1-A1E0S1"
    And I click "Members"
    Then I should see the heading "Behat1-A1E0S1 members"

  Scenario: Org admins can see the manage billing link and menu item
    Given group entities:
      | type         | label        | field_license_status | field_current_expiration_date | field_stripe_customer_id | id    |
      | organization | Behat-A1E0S1 | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 40001 |
    And users:
      | name                 | mail                 | status | uid    | roles             |
      | testing@example.com  | testing@example.com  | 1      | 500001 | active_org_member |
    And user with name "testing@example.com" is a member of group with title "Behat-A1E0S1" and group role "admin"
    When I am logged in as "testing@example.com"
    And I am at "/group/40001"
    Then I should see the link "Billing"

  Scenario: Global billing admins can see the manage billing link and menu item.
    Given group entities:
      | type         | label        | field_license_status | field_current_expiration_date | field_stripe_customer_id | id    |
      | organization | Behat-A1E0S1 | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 40001 |
    And users:
      | name                 | mail                 | status | uid    | roles                            |
      | testing@example.com  | testing@example.com  | 1      | 500001 | active_org_member, billing_admin |
    When I am logged in as "testing@example.com"
    And I am at "/group/40001"
    Then I should see the link "Billing"

  Scenario: Global admins can see the manage billing link and menu item
    Given group entities:
      | type         | label        | field_license_status | field_current_expiration_date | field_stripe_customer_id | id    |
      | organization | Behat-A1E0S1 | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 40001 |
    And users:
      | name                 | mail                 | status | uid    | roles                            |
      | testing@example.com  | testing@example.com  | 1      | 500001 | active_org_member, administrator |
    When I am logged in as "testing@example.com"
    And I am at "/group/40001"
    Then I should see the link "Billing"

  Scenario: Org admin of one group cannot see anothe groups links
    Given group entities:
      | type         | label         | field_license_status | field_current_expiration_date | field_stripe_customer_id | id    |
      | organization | Behat-A1E0S1  | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 40001 |
      | organization | Behat2-A1E0S1 | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 40002 |
    And users:
      | name                 | mail                 | status | uid    | roles             |
      | testing@example.com  | testing@example.com  | 1      | 500001 | active_org_member |
    And user with name "testing@example.com" is a member of group with title "Behat-A1E0S1" and group role "admin"
    And user with name "testing@example.com" is a member of group with title "Behat2-A1E0S1"
    When I am logged in as "testing@example.com"
    And I am at "/group/40002"
    Then I should not see the link "Manage Billing"
    And I should not see the link "Billing"
    And I am at "/group/40001"
    Then I should see the link "Billing"

  Scenario: Org admin when accessing an expired legacy groups billing route gets message regarding legacy system.
    # If this starts failing be sure that this date is in the future, but never more than 2 years in the future.
    Given group entities:
      | type         | label         | field_license_status | field_current_expiration_date | id    |
      | organization | Behat-A1E0S0  | active               | 2124-04-10T00:00:00           | 40001 |
    And users:
      | name                 | mail                 | status | uid    | roles             |
      | testing@example.com  | testing@example.com  | 1      | 500001 | active_org_member |
    And user with name "testing@example.com" is a member of group with title "Behat-A1E0S0" and group role "admin"
    When I am logged in as "testing@example.com"
    And I am at "/group/40001/manage-billing"
    Then I should see the text "Billing for this Organization was done in our legacy system and requires an administrator to make changes. Please contact a site administrator for further help."

  @javascript
  Scenario: Org admin can setup an organization with automatic billing.
    # If this starts failing be sure that this date is in the future, but never more than 2 years in the future.
    Given group entities:
      | type         | label         | field_license_status | field_current_expiration_date | id    |
      | organization | Behat-A1E0S0  | active               | 2024-04-10T00:00:00           | 40001 |
    And users:
      | name                 | mail                 | status | uid    | roles             |
      | testing@example.com  | testing@example.com  | 1      | 500001 | active_org_member |
    And user with name "testing@example.com" is a member of group with title "Behat-A1E0S0" and group role "admin"
    When I am logged in as "testing@example.com"
    And I am at "/group/40001/manage-billing"
    Then I should see the text "The license for this account was created via our legacy system"
    And I should see the text "Please subscribe via our new system prior to the expiration date using the form below to enable auto collection."
    And I should see the text "Collection via our new system won't begin until"
    And I should see the text "If you would like to decrease your subscription level, please"
    And I wait 5 seconds
    # Form fails when no plan selected
    And I wait 10 seconds
    Then I should see an "#card-element iframe" element
    Then I switch to iframe via css selector "#card-element iframe"
    And I fill in "cardnumber" with "4242 4242 4242 4242"
    And I fill in "exp-date" with "12/32"
    And I fill in "cvc" with "123"
    And I wait 5 seconds
    And I fill in "postal" with "12345"
    And I switch back to the main window.
    And I press the "registration-submit" button
    And I wait 10 seconds
    Then I should see the text "Please select a plan" in the "messages" region
    And I select "50 Team Members, 50.00 per month" from "Pick Your Plan"
    Then I switch to iframe via css selector "#card-element iframe"
    And I fill in "cardnumber" with "4242 4242 4242 4242"
    And I fill in "exp-date" with "12/32"
    And I fill in "cvc" with "123"
    And I wait 5 seconds
    And I fill in "postal" with "12345"
    And I switch back to the main window.
    And I press the "registration-submit" button
    And I wait 10 seconds
    Then I should see the text "Thank you, your account has been updated via our new system." in the "messages" region
    # Confirm Stripe ID Field
    And I am not logged in
    When I am logged in as a user with the 'administrator' role
    And I am at "/group/40001/edit"
    Then the "Stripe Customer ID" field should not be empty
