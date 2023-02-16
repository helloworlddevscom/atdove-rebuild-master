@api
Feature: All custom AtDove reports work as expected.

  Scenario: Menu links for reports are all present.
    Given I am logged in as a user with the "administrator" role
    Then I should see the link "RACE Report" in the "toolbar" region

  @wip @todo
  Scenario: RACE report view works as expected.
    Given I am logged in as a user with the "administrator" role
    Given users:
      | name                     | mail                     | status| uid  | field_first_name | field_last_name | field_license_id_number | field_license_state | field_license_type |
      | bobadobalina@example.com | bobadobalina@example.com | 1     | 9999 | Bob              | Dobalina        | FAKELIC12345            | or                  | vet                |
    When I click "RACE Report" in the "toolbar" region
    Then I should see the heading "R.A.C.E. Report"
    And I should see the text "bobadobalina@example.com" in the "Behat Test article" row

  # @TODO - Scenario: RACE report csv export works as expected.
