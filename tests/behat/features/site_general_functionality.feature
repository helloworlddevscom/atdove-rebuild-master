@api
Feature: Basic / normal parts of Drupal all remain working as expected.

  Scenario: I can login as a normal user.
    Given users:
      |name                    | mail                     | status| uid  | field_first_name | field_last_name |
      |bobadobalina@example.com| bobadobalina@example.com | 1     | 9999 | Bob              | Dobalina        |
    Given I am logged in as a user with the 'administrator' role
    When I am at "/user/9999/edit"
    And I fill in "Password" with "getm31n!"
    And I fill in "Confirm password" with "getm31n!"
    And I press the "Save" button
    Then I should see the text "The changes have been saved."
    And I am not logged in
    When I am at "/user/"
    And I fill in "Email address" with "bobadobalina@example.com"
    And I fill in "Password" with "getm31n!"
    And I press the "Scrub In" button
    Then I should see "Bob Dobalina" in the "span.profile-name" element

  Scenario: Drupal one time login links work
    Given users:
      |name                    | mail                     | status| uid  | field_first_name | field_last_name |
      |bobadobalina@example.com| bobadobalina@example.com | 1     | 9999 | Bob              | Dobalina        |
    When I visit the one time login link for user with id 9999
    Then the response status code should be 200

  Scenario: Administrators can access key pages without an issue.
    Given I am logged in as a user with the 'administrator' role
    And I am at "admin/content"
    Then the response status code should be 200
    And I am at "admin/people"
    Then the response status code should be 200
    And I am at "admin/structure/groups"
    Then the response status code should be 200
    And I am at "admin/content/ilt-result"
    Then the response status code should be 200
    And I am at "admin/content/ilt"
    Then the response status code should be 200
    And I am at "admin/content/moxtra/meeting_result"
    Then the response status code should be 200
    And I am at "admin/content/moxtra/meeting"
    Then the response status code should be 200
    And I am at "admin/content/media"
    Then the response status code should be 200

  Scenario: Users can view other users profiles
    Given users:
      | name                 | mail               | status | uid    | roles             |
      | standard@example.com | gadmin@example.com | 1      | 500004 | active_org_member |
      | normal@example.com   | normal@example.com | 1      | 500005 | active_org_member |
    And I am logged in as "standard@example.com"
    When I visit "/user/500005"
    Then the response status code should be 200
