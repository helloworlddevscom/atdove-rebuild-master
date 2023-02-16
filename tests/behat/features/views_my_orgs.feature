@api
Feature: View listing "my orgs" works and is linked in user dropdown menu.

  Scenario: Org admins can see the manage billing link and menu item
    Given group entities:
      | type                  | label        | field_license_status | field_current_expiration_date | field_stripe_customer_id | id    |
      | organization          | Behat-A1E0S1 | active               | 2028-04-05T00:00:00           | BEHAT_PASS               | 40001 |
    And users:
      | name                 | mail                 | status | uid    | roles             |
      | testing@example.com  | testing@example.com  | 1      | 500001 | active_org_member |
    And user with name "testing@example.com" is a member of group with title "Behat-A1E0S1" and group role "admin"
    When I am logged in as "testing@example.com"
    Then I should see the text "My Orgs" in the "userDropDownMenu" region
    And I click "My Orgs" in the "userDropDownMenu" region
    Then the response status code should be 200
    Then I should see the heading "My Organizations"
    And I should see the text "Organization" in the "Behat-A1E0S1" row
