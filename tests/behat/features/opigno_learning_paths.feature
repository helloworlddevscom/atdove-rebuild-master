@api
Feature: Learning Paths are configured properly.

  @javascript
  Scenario: An org-admin of a group can access a training plan in that group.

    Given group entities:
      | type         | label                 | field_license_status | id   |
      | organization | Behat-Test            | active               | 4001123155 |
      | learning_path | LP-Test              |                      | 4001123267 |
    And users:
      | name                                    | mail                          | status | uid  | roles             |
      | orgadminofGroup@example.com             | orgadminofGroup@example.com   | 1      | 5001123 | active_org_member |
      | testing@example.com  | testing@example.com  | 1      | 5001124 | active_org_member |
    And Group memberships:
      | uid     | gid         | roles                 |
      | 5001123 | 4001123155    |  organization-admin   |
    And the group "Behat-Test" has a subgroup of type "learning_path" titled "Atdove Learning Path"
    When I am logged in as "orgadminofGroup@example.com"
    And I am at "/group/4001123155/training-plans"
    And I wait for AJAX to finish
    And I should see the text "Atdove Learning Path"
    And I click "Atdove Learning Path"
    Then I click the "button.dropdown-toggle" element
   # And I am at "/group/4001123267/edit"
    And I should see the text "Edit"

  @javascript
  Scenario: A non-org-admin of a group cannot access a training plan in that group.

    Given group entities:
      | type         | label                 | field_license_status | id   |
      | organization | Behat-Test            | active               | 40011231 |
    And users:
      | name                  | mail                 | status | uid     | roles             |
      | nonadmin@example.com  | testing@example.com  | 1      | 5001124 | active_org_member |
    And Group memberships:
      | uid     | gid         | roles                 |
      | 5001124 | 40011231    |                       |
    And the group "Behat-Test" has a subgroup of type "learning_path" titled "Atdove Learning Path"
    When I am logged in as "nonadmin@example.com"
    And I am at "/group/40011231/training-plans"
    And I should see the text "not authorized"
